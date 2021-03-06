<?php

namespace AppBundle\Repository;
use Doctrine\ORM\Query\ResultSetMapping;
/**
 * OrdineRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class OrdineRepository extends \Doctrine\ORM\EntityRepository
{
    public function RicercaOrdini($em,$str){
        $query ='
               SELECT temp.*, stato.Nome as stato
               FROM	(SELECT c.nome, c.cognome, o.id as codice, max(s.id) as idstato
                        FROM cliente c, ordine o, stato s, possedere p    	
                        WHERE c.id= o.clienteId and p.statoId= s.id and p.statoId>7 and p.ordineId=o.id and( c.nome LIKE "'.$str.'" or c.cognome LIKE "'.$str.'" or c.username LIKE "'.$str.'" or c.CodiceFiscale LIKE "'.$str.'" or o.id LIKE "'.$str.'" ) 
                        Group by (o.id)
                        ) 
                    as temp, stato
                WHERE stato.id=temp.idstato ';
      
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
               
       
    }  
    
    public function RicercaOrdiniByCliente($em, $idCliente){
        
        $query='
                    SELECT temp.*, stato.Nome as stato 
                    FROM (	
                            SELECT o.id as codiceordine, max(s.id) as idstato, o.data, o.graficaId as idgrafica, o.articoloId as articolo, sp.Denominazione as sitoproduzione, sg.Denominazione as studiografico 
                            FROM  ordine o, stato s, possedere p, sito_produzione sp, studio_grafico sg, stato_produzione sprod, operatore op, grafica g, grafico gr 
                            WHERE o.ClienteId='.$idCliente.' and p.statoId= s.id and p.ordineId=o.id and sprod.OrdineId=o.id and sprod.OperatoreId=op.id AND op.SitoProduzioneId=sp.id and g.id=o.graficaId and g.GraficoId=gr.id and gr.StudioGraficoID=sg.id 
                            Group by (o.id)
                            ORDER By o.data DESC
                        ) as temp, stato 
                    WHERE stato.id=temp.idstato';
        
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function  DatiOrdineById($em, $idOrdine){
        $query='
                    SELECT temp.*, stato.Nome as stato 
                    FROM (	
                            SELECT  c.nome, c.cognome, c.id as idcliente, o.sconto, a.PrezzoVendita as prezzo,  o.quantita, o.id as codiceordine, max(s.id) as idstato, o.data, o.graficaId as idgrafica, o.articoloId as articolo, sp.Denominazione as sitoproduzione,sg.Denominazione as studiografico
                            FROM  ordine o, stato s, possedere p, sito_produzione sp, studio_grafico sg, stato_produzione sprod, operatore op, grafica g, grafico gr, cliente c, articolo a 
                            WHERE o.ClienteId=c.id and p.statoId= s.id and p.ordineId=o.id and sprod.OrdineId=o.id and sprod.OperatoreId=op.id AND op.SitoProduzioneId=sp.id and g.id=o.graficaId and g.GraficoId=gr.id and gr.StudioGraficoID=sg.id and a.id=o.articoloid and o.id='.$idOrdine.'
                            Group by (o.id)
                            ORDER By o.data DESC
                        ) as temp, stato 
                    WHERE stato.id=temp.idstato';
        
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function  EventiOrdineById($em, $idOrdine){
        $query='
                    SELECT temp.*, s.nome
                    FROM    (SELECT p.data, p.Descrizione, p.StatoId
                            FROM possedere p
                            WHERE p.OrdineId='.$idOrdine.') as temp, stato s
                    WHERE temp.StatoId=s.id';
        
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function DatoRequest($request){
        if($request->get('idcliente')){
            $ris=array("tipo"=>"idcliente","dato"=>$request->get('idcliente'));
        }elseif($request->get('idarticolo')){
            $ris=array("tipo"=>"idarticolo","dato"=>$request->get('idarticolo'));
        }elseif ($request->get('quantita')) {
            $ris=array("tipo"=>"quantita","dato"=>$request->get('quantita'));
        }elseif ($request->get('bozza')) {
            $ris=array("tipo"=>"bozza","dato"=>$request->get('bozza'));
        }elseif ($request->get('commento')) {
            $ris=array("tipo"=>"commento","dato"=>$request->get('commento'));
        }else{ 
            $ris=null;}
        return $ris;
    }
    public function CheckOrdine($ordine){
        if(array_key_exists("idarticolo",  $ordine )){
            if(array_key_exists("idcliente",  $ordine )){
                if(array_key_exists("quantita",  $ordine )){
                    if(array_key_exists("bozza",  $ordine ) or array_key_exists("idbozza",  $ordine )){
                        if(array_key_exists("commento",  $ordine )){
                            $ris= "completo";
                        }else{
                            $ris= "commento";
                        }
                    }else{
                        $ris= "bozza";
                    }
                }else{
                    $ris="quantita";
                }
            } else {
                $ris="idcliente";
            }
        }else{
            $ris="idarticolo";
        }
        return $ris;
    }
    
    public function OrdineDaSessione($em,$session){
        $unOrdine= new \AppBundle\Entity\Ordine();
         if(array_key_exists ( "idordine" , $session->get('ordine') )){
            $unOrdine->setId( $session->get('ordine')['idordine']);
        }
        $unOrdine->setArticoloId( $session->get('ordine')['idarticolo']);
        $unOrdine->setClienteId($session->get('ordine')['idcliente']);
        $idPrinkino=$em->getRepository('AppBundle:Prinkino')->getIdByUsername($em,$session->get('user'));
        $unOrdine->setPrinkinoId($idPrinkino);
        $IdBozza=$this->GeneraIdBozza($em);
        $path= $this->SalvaBozza($IdBozza, $session->get('ordine')['bozza']);
        $unOrdine->setBozzaGrafica($IdBozza);
        $unOrdine->setCommento($session->get('ordine')['commento']);
        $unOrdine->setQuantita($session->get('ordine')['quantita']);
        return $unOrdine;
    }
    
    public function GeneraIdBozza($em){
        $query=$em->createQuery('
                    Select max(o.bozzaGrafica)
                    From AppBundle:Ordine o
                    ');
        $ris = $query->getSingleScalarResult()+1;
        return $ris;
    }
    
    public function SalvaBozza($id,$svg){
        $path="bozze/".$id.".svg";
        $file=fopen($path,"w+");
        fwrite($file,$svg);
        fclose($file);
        return $path;
        
    }


    public function RiepilogoOrdine($unOrdine, $em){
        if($unOrdine->getId()!=null){
            $riepilogo[]=array("tipo" => "codice" ,"contenuto" => $unOrdine->getId());
        }
        if($unOrdine->getArticoloId()!=null){
            $riepilogo[]=array("tipo" => "articolo" ,"contenuto" => $unOrdine->getArticoloId());
        }
        if($unOrdine->getClienteId()!=null){
            $cliente=$em->getRepository("AppBundle:Cliente")->findOneById($unOrdine->getClienteId());
            $riepilogo[]=array("tipo" => "cliente" ,"contenuto" => $cliente->getNome()." ".$cliente->getCognome());
        }
        if($unOrdine->getPrinkinoId()!=null){
            $riepilogo[]=array("tipo" => "Prinkino" ,"contenuto" => $unOrdine->getPrinkinoId());
        }
        if($unOrdine->getBozzaGrafica()!=null){
            $riepilogo[]=array("tipo" => "IDbozza" ,"contenuto" => $unOrdine->getBozzaGrafica());
        }
        if($unOrdine->getCommento()!=null){
            $riepilogo[]=array("tipo" => "commento" ,"contenuto" => $unOrdine->getCommento());
        }
        if($unOrdine->getQuantita()!=null){
            $riepilogo[]=array("tipo" => "quantita" ,"contenuto" => $unOrdine->getQuantita());
        }
        return $riepilogo;
    }
    
    
    public function CreaOrdine($em, $unOrdine){
        //inserisco ordine nella tabella Ordine
        $unOrdine->setGraficaId(0);
        $data= new \DateTime("now");
        $unOrdine->setData($data);
        $unOrdine->setSconto(0);
        $em->persist($unOrdine);
        $em->flush();
        //inserisco stato nella tabella possedere
        $unoStato= new \AppBundle\Entity\Possedere();
        $unoStato->setData($data);
        $unoStato->setOrdineId($unOrdine->getId());
        $unoStato->setStatoId(8);//stato 8 => ordine completato
        $em->persist($unoStato);
        $em->flush();
        //inserisco fase di produzione non avviata in tabella stato produzione
        $unaProduzione=new \AppBundle\Entity\StatoProduzione();
        $unaProduzione->setFaseProduzioneId(0);
        $unaProduzione->setOperatoreId(0);
        $unaProduzione->setOrdineId($unOrdine->getId());
        $em->persist($unaProduzione);
        $em->flush();
    }
    

    //non utilizzato il db gestisce direttamente gli id
      public function GeneraIdOrdine($em){
        $query=$em->createQuery('
                    Select max(o.id)
                    From AppBundle:Ordine o
                    ');
        $ris = $query->getSingleScalarResult()+1;
      return $ris;
              
      }
      
      public function SospendiOrdine($em,$session){
        $unOrdine=new \AppBundle\Entity\Ordine();
        $idPrinkino=$em->getRepository('AppBundle:Prinkino')->getIdByUsername($em,$session->get('user'));
        $unOrdine->setPrinkinoId($idPrinkino);
        $data= new \DateTime("now");
        $unOrdine->setData($data);
        //inserisco all'interno di ordine solo i dati presenti nella sessione
         
        if(array_key_exists ( "idarticolo" , $session->get('ordine') )){
            $unOrdine->setArticoloId( $session->get('ordine')['idarticolo']);
        }
        if(array_key_exists ( "idcliente" , $session->get('ordine') )){
            $unOrdine->setClienteId($session->get('ordine')['idcliente']);
        }
        if(array_key_exists ( "commento" , $session->get('ordine') )){
            $unOrdine->setCommento($session->get('ordine')['commento']);
        }
        if(array_key_exists ( "quantita" , $session->get('ordine') )){
            $unOrdine->setQuantita($session->get('ordine')['quantita']);
        }
         if(array_key_exists ( "idbozza" , $session->get('ordine') )){
            $unOrdine->setBozzaGrafica($session->get('ordine')['idbozza']);
        }
        if(array_key_exists ( "bozza" , $session->get('ordine') )){
            $IdBozza=$this->GeneraIdBozza($em);
            $path= $this->SalvaBozza($IdBozza, $session->get('ordine')['bozza']);   
            $unOrdine->setBozzaGrafica($IdBozza);  
        }
        if(array_key_exists ( "idordine" , $session->get('ordine') )){
            $unOrdine->setId( $session->get('ordine')['idordine']);
            $em->merge($unOrdine);
        } else {
            $em->persist($unOrdine);
        }
        
        //carico l'ordine nel DB
        $em->flush();
        //Assegno all'ordine appena creato uno stato in inserimento
        $idStato=count($session->get('ordine'));
        $unoStato= new \AppBundle\Entity\Possedere();
        $unoStato->setData($data);
        $unoStato->setOrdineId($unOrdine->getId());
        $unoStato->setStatoId($idStato);
        //Controllo se è già presente nel DataBase
        $temp=$em->getRepository("AppBundle:Possedere")->find(array("ordineId"=>$unoStato->getOrdineId(),"statoId"=>$unoStato->getStatoId()));
        if(!$temp){
            $em->persist($unoStato);
            echo "nessun oggetto trovato";
        } else {
            $em->merge($unoStato);
        }
        $em->flush();
        
        return;
      }
   
    public function RiepilogoOrdineSospeso($ordine ,$em){
        $ris=array();
        if(array_key_exists("idarticolo",  $ordine )){
            $ris[]=array("tipo" => "idarticolo", "contenuto"=>$ordine['idarticolo']);
        }
        if(array_key_exists("idcliente",  $ordine )){
            $cliente=$em->getRepository("AppBundle:Cliente")->findOneById($ordine['idcliente']);
            $ris[]=array("tipo" => "idcliente", "contenuto"=>$cliente->getNome()." ".$cliente->getCognome());
        }
        if(array_key_exists("quantita",  $ordine )){
            $ris[]=array("tipo" => "quantita", "contenuto"=>$ordine['quantita']);
        }
        if(array_key_exists("bozza",  $ordine )){
            $ris[]=array("tipo" => "bozza", "contenuto"=>$ordine['bozza']);
        }
        if(array_key_exists("commento",  $ordine )){
            $ris[]=array("tipo" => "commento", "contenuto"=>$ordine['commento']);
            }
        return $ris;
    }
    
     public function OrdineSospesoToSessione($unOrdine){
        if($unOrdine->getId()!=null){
            $riepilogo["idordine"]=$unOrdine->getId();
        }
        if($unOrdine->getArticoloId()!=null){
            $riepilogo["idarticolo"]= $unOrdine->getArticoloId();
        }
        if($unOrdine->getClienteId()!=null){
            $riepilogo["idcliente"]= $unOrdine->getClienteId();
        }
        if($unOrdine->getBozzaGrafica()!=null){
            $riepilogo["idbozza"]= $unOrdine->getBozzaGrafica();
        }
        if($unOrdine->getCommento()!=null){
            $riepilogo["commento"]= $unOrdine->getCommento();
        }
        if($unOrdine->getQuantita()!=null){
            $riepilogo["quantita"]= $unOrdine->getQuantita();
        }
        return $riepilogo;
    }
    
    public function daEseguire($em,$idSP){
            $query=('
                    Select OD.*
                    From OrdineDati OD
                    Where idsitoproduzione='.$idSP.' and idstato=19
                    ');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
     public function inProduzione($em,$idSP){
            $query=('
                    Select OD.*
                    From OrdineDati OD
                    Where idsitoproduzione='.$idSP.' and idstato=20
                    ');
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

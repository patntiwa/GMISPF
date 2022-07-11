<?php
   
  include'./Fonction/connexion.php';
               
  if(isset($_GET['idrapport'])){

    $id = $_GET['idrapport'];
     
     // requete pour supprimer
 

    $delete ="DELETE FROM rapport WHERE idrapport= '".$id."' ";

    $result = $base->query($delete);

?>
 <script>alert'Suppression reussie';</script>
<?php     
    
    if($result){
        header('location:consultation.php');
    }

  }   
?>
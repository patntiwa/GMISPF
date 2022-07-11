<?php
   
	$Libelle=$Lieu=$Installations=$etat=$Remplacer=$Niveau=$Observation='';
	
?>
<!DOCTYPE html>

<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>GMISPF Service</title>
	<link rel="stylesheet" type="text/css" href="./css/head.css">
	<link rel="shortcut icon" type="images/x-icon" href="./images/beac-logo.ico">
	
</head>
<body>
	<!--entete-->
<div class="entete">
			               <h1>GMISPF service</h1>
			<div class="barre1">
			        				
									  <a href="accueil.php" class='btn'>Accueil</a>

									  <a href="consultation.php" class='btn'>consulter</a>

									  <a class='btn' class="active"  href="deconnexion.php" >Deconnexion</a>
                   				 
        	</div>  
</div>
	 <!--formulaire d'insertion-->
<div class="corps"> 
		   
	  <div class="form">
          	
		     <form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="post">	
		      	 		<h2 align="center" style="margin-top:5%;margin-bottom:5%;">Compte Rendu du Jour</h2><br><br>	
                 <table class="tableau">

						<tr><td> <label for="Libelle">Libelle du probleme</label> </td></tr>
				 
						<tr><td><input type="text" class="" name="Libelle" value='<?php  echo $Libelle; ?>' required/> </td></tr>
	
						  <tr><td> Emplacement </td></tr>
						
						  <tr><td><select name="Lieu" value="">
											   
						                       <option>Immeuble de bureaux</option>
											   
											   <option>Villa de direction</option>
											   
											   <option>Guerites</option>   

											   <option>Baches à eau</option>
											   
											   <option>Vestiaires</option>
											   
											   <option>Gymnase</option>   
				       				 </select> </td></tr>
				 
						  <tr><td><label for="installation">installation concernée</label> </td></tr>
					  
						  <tr><td>
							         <select name="Installation" value="">
											   
						                       <option>Sanitaire</option>
											   
											   <option>Plomberie</option>
											   
											   <option>Forage</option>    
				       				 </select>
				         </td></tr> 
							
							 <tr><td><label for="remplacer">Piece à remplacer</label></td></tr>
				   
							  <tr><td><input type="text" name="Remplacer" value='<?php echo $Remplacer ?>' required/> </td></tr>
				   
							 <tr><td><label for="etat">Etat de l'equipement</label> </td></tr>
				   
							<tr><td>
										<select class="" name="etat" value="<?php echo $etat ?>">
													<option>A Suivre</option>
													<option>Moyen</option>
													<option>Critique</option>
										</select>
							</td></tr>
							
							<tr><td><label for="niveau">Niveau d'urgence</label></td></tr>
					
							 <tr><td>	
								  <select class="" name="Niveau" value='<?php echo $Niveau ?>'>
						   					<option style="">Faible</option>
						   					<option>Moyen</option>
						   					<option>urgent</option>
										</select>
							</td></tr>
							
							<tr><td><label for="observation"> Observations</label></td></tr>
					
								<tr><td><textarea rows="5" name="Observation" value='<?php echo $Observation ?>'></textarea></td></tr>
				   
								<tr><td> <input type="submit" class="submit" name="Soumettre" Value="Envoyer"></td></tr>
<?php
      if (isset($_POST['Soumettre']))
       {
      
             //RECUPERATION DES VALEURS DU FORMULAIRE

                    $Libelle=$_POST['Libelle'];

 	                $Lieu=$_POST['Lieu'];

 	                $Installation=$_POST['Installation'];


 	                $Remplacer=$_POST['Remplacer'];

 	                $etat=$_POST['etat'];

 	                $Niveau=$_POST['Niveau'];
 	                
                    $Observation=$_POST['Observation'];
  
                  //CONNEXION A LA BD
					
				  include'./Fonction/connexion.php';
				   
				  //INSERER LES DONNEES DANS LA BD
			   
				  $insert="INSERT INTO `rapport`( `Daterap`, `Libelle`, `Lieu`, `Installation`, `Remplacer`, `etat`, `Niveau`, `Observation`) 
				    VALUES (NOW(),'$Libelle','$Lieu','$Installation','$Remplacer','$etat','$Niveau','$Observation')";
            
                	 $query=mysqli_query($base,$insert);
                	 $Insert2="INSERT INTO `historique`( `Daterap`, `Libelle`, `Lieu`, `Installation`, `Remplacer`, `etat`, `Niveau`, `Observation`) 
				    VALUES (NOW(),'$Libelle','$Lieu','$Installation','$Remplacer','$etat','$Niveau','$Observation')";

				    $query2=mysqli_query($base,$Insert2);

              if ($query){

                echo  "<script> alert ('enregistrement reussi'); </script>";

              }
              else{

                echo "<script> alert ('Echec d'enregistrement'); </script>";
           
              }


       }
 ?> 

						</table>			
		     </form>
	 </div>	

</div>		
			<!--fin du formulaire d'insertion-->
		    		  	
	  	
			<div class="piedsdepage">
			   <h2>©2020</h2>
			</div>
			

</body>
</html>			
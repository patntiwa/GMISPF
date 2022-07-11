<?php
	$Libelle=$Lieu=$Installations=$etat=$Remplacer=$Niveau=$Observation='';
?>											          
<!DOCTYPE html>	 
<html lang="fr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>GMISPF Service</title>
	<link rel="stylesheet" type="text/css" href="./css/head.css">
	<link rel="shortcut icon" type="images/x-icon" href="./images/beac-logo.ico">
</head>
<body>
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
           <div>
     <?php
            
            include'./Fonction/connexion.php';

             $id=$_GET['idrapport'];

            $select="SELECT * FROM rapport WHERE idrapport='$id'";

            $query=mysqli_query($base,$select);

            $result=mysqli_fetch_assoc($query);

						if (isset($_POST['update']))
						{
							$Libelle=$_POST['Libelle'];
							$Lieu=$_POST['Lieu'];
							$Installation=$_POST['Installation'];
							$Remplacer=$_POST['Remplacer'];
							$etat=$_POST['etat'];
							$Niveau=$_POST['Niveau'];
							$Observation=$_POST['Observation'];
							
							
							mysqli_query($base,"UPDATE rapport SET Libelle='$Libelle',Lieu='$Lieu',Installation='$Installation', Remplacer='$Remplacer', etat='$etat',Niveau='$Niveau', Observation='$Observation'
				WHERE idrapport='$id'");
							header('location:consultation.php');
		}
    ?> 

		      <form action="#" method="post">

				
		      	 <center><h2 style="margin-top:5%;margin-bottom:5%;">Mettre à jour le Compte Rendu envoyer</h2></center><br><br>	
				 <label for="equipement">Libelle du probleme</label></td>
				 <input type="text" class="" name="Libelle" value="<?php echo $result['Libelle'];?>" required/></td>
	
				  <label for="emplacement">Emplacement</label></td>
	              <input type="text" class="form-control" name="Lieu" value="<?php echo  $result['Lieu'];?>" required/>
				  <label for="installation">installation concernée</label>
                       <select name="Installation" value="<?php echo $$result['Installation']; ?>">
					       <option>Sanitaire</option>
					       <option>Plomberie</option>
					       <option>Forage</option>    
				       </select>
				   <label for="remplacer">Piece à remplacer</label>
			       <input type="text" name="Remplacer" value="<?php echo $result['Remplacer'];?>" required/>
				   <label for="etat">Etat de l'equipement</label>
				   <select class="" name="etat" value="<?php echo $result['etat']; ?>">
						<option>A Suivre</option>
						<option>Moyen</option>
						<option>Critique</option>
					</select>
					<label for="niveau">Niveau d'urgence</label>
					<select class="" name="Niveau" value="<?php echo $result['Niveau']; ?>">
						   
						   <option>Faible</option>
						   <option>Moyen</option>
						   <option>urgent</option>
					    </select>
	                <label for="observation">Vos Observations</label>
                    <textarea rows="5" name="Observation" value="<?php echo $result['Observation'];?>"></textarea>
				   
				     <input type="submit" class="submit" value="Mettre à jour" name="update">  
			    </form>
	       </div>	
	     </div>		
			<!--fin du formulaire d'insertion-->
			<div class="piedsdepage">
			  <h2>©2020</h2>
			</div>
</body>
</html>			

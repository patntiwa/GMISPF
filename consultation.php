
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
			  <div class="entete">
			               <h1>GMISPF service</h1>
			   				<div class="barre1">
			        				
									  <a href="accueil.php" class='btn'>Accueil</a>
									  <a href="insertion.php" class='btn'>Inserer</a>
									  <a class='btn' class="active"  href="deconnexion.php">Deconnexion</a>
                   				 
              				</div>  
              </div>
			 
			  <div class="corps1">
			                 
                                 <div class="fonddepage">
								 
										<table id=table  class="table">
										<caption><h1 align="center" color="black" style="margin-top:5%;margin-bottom:5%;"><u> RECAPITULATIFS DES DISFONCTIONNEMENTS</u></h1></caption>	
										<thead > <!-- En-tête du tableau -->

											  	<tr>
											  		<th>N°</th>
											  		<th>Libellé du probleme</th>
											  		<th>Date</th>
											  		<th>Emplacement</th>
											  		<th>installation concernée</th>
											  		<th>Piece à remplacer</th>
											  		<th>Vos Observations</th>
											  		<th>Etat de l'equipement</th>
											  		<th>Niveau d'urgence</th>
											  		<th>Solution Alternative</th>
											  		<th colspan="1">Option</th>
											  	</tr>
										    </thead>
										    <tbody> <!-- Corps du tableau -->	  	
												<?php
										            $sql="SELECT * FROM rapport";
										                  include './Fonction/connexion.php';
										                  $res=mysqli_query($base,$sql);
										                    mysqli_close($base);
										                  while($ligne=mysqli_fetch_assoc($res))
										                  {
											    	?>			  
										        	    <tr>
										        	         <td><?php echo $id=$ligne['idrapport'];?></td>
										        	         <td><?php echo $ligne['Libelle'];?></td>
										        	         <td><?php echo $ligne['Daterap'];?></td>
										        	         <td><?php echo $ligne['Lieu'];?></td>
										        	         <td><?php echo $ligne['Installation'];?></td>
										        	         <td><?php echo $ligne['Remplacer'];?></td>
										        	         <td><?php echo $ligne['Observation'];?></td>
										        	         <td><?php echo $ligne['etat'];?></td>
										        	         <td><?php echo $ligne['Niveau'];?></td>
										        	         <td><?php echo $ligne['Niveau'];?></td>
										        	         <td><a class="btn" href="update.php?idrapport=<?php echo $id;?>">modifier</a></td>
										        	     </tr>
												<?php
														  }          
										        ?>
										    </tbody>    
										</table>
								   </div>
			   </div>	
			
			   <div class="piedsdepage">
			         <h2>©2020</h2>
			   </div>



			  <!-- JS, Popper.js, and jQuery -->

</body>
</html>				   	  	
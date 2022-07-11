<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>GMISPF Service</title>
	<link rel="stylesheet" type="text/css" href="./css/head.css">
	<link rel="shortcut icon" type="images/x-icon" href="./images/beac-logo.ico">
    <link href="autre.css" rel="stylesheet">
	<style>
.custom-select {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}
</style>
</head>
<body>
<div class="entete">
			               <h1>GMISPF service</h1>
			   				<div class="barre1">
			        				
									  <a href="accueil.php" class='btn'>Accueil</a>
									  <a href="consulter2.php" class='btn'>Consulter</a>
									  <a class='btn' class="active"  href="deconnexion.php">Deconnexion</a>
                   				 
              				</div>  
              </div>
			 
			  <!--formulaire d'insertion-->

 
      			<div >
      				<table style="width:80%;margin:auto;margin-top:5%;margin-left:20%;">
        						<tr>
        						   <td>
        							<select>
        							<option></option>
        							<option></option>
        							<option></option>
        						    </select>
        						 </td>
        						  <td>
        							<select>
        							<option></option>
        							<option></option>
        							<option></option>
        						    </select>
        						 </td>
        						  <td>
        							<select>
        							<option></option>
        							<option></option>
        							<option></option>
        						    </select>
        						 </td>
      							<td><a href="#" name="Search" class="btn"> Recherche</a></td></tr>
      				</table>			
				</div> 
  

			  <div class="corps">
			  
			           
			          
            	<table id=table  class="table">
				<caption><h1 align="center" style="margin-top:5%;margin-bottom:5%;"><u> HISTORIQUE DES DISFONCTIONNEMENTS</u></h1></caption>
					<thead> <!-- En-tête du tableau -->
 
					  	<tr>
					  		<th>N°</th>
					  		<th style="width:30%;text-align:center;margin:auto;">Libellé du probleme</th>
					  		<th style="width:10%;">Date</th>
					  		<th style="width:20%;">Emplacement</th>
					  		<th style="width:30%;">installation concernée</th>
					  		<th style="width:20%;">Piece à remplacer</th>
					  		<th>Vos Observations</th>
					  		<th>Etat de l'equipement</th>
					  		<th>Niveau d'urgence</th>
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
				                                             </tr>
												<?php
														  }          
										        ?>
				    </tbody>    
			    </table>
			   </div>	
			   <div class="piedsdepage">
			         <h2>©2020</h2>
			   </div>
</body>
</html>				   	  	
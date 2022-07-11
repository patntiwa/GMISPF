<?php
  $Username="";
  session_start();
  $_SESSION['Username'] = $Username;
?>				      		
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	
	<title>GMISPF Service</title>
	
	
	<link rel="stylesheet" type="text/css" href="./css/accueil.css">
	<link href="css/font-awesome.min.css" rel="stylesheet">
	<link rel="shortcut icon" type="images/x-icon" href="./images/beac-logo.ico">
    

</head>
<body>
	<!--entete-->
		    <div class="fond-head">

             <div class="fond-head-texture">
            	      <div class="container">
						    <div class="barre1">
							    <div class="entetetext">  
							       <h1 color="black">GMISPF service</h1>	
								</div>

					           <div class="barre-menu">
								  <a href="accueil.php" class="btn">Accueil</a></li>
								  <a href="about.php" class="btn">A propos</a></li>
								  <a href="deconnexion.php" class="btn">Deconnexion</a></li>
                               </div>  
            			    </div> 
				     </div>
            </div>		
      </div>  
	 <!--fin entete-->
		<!--corps de la page-->	
		<div class="fond-box">	
			
			<div class="box">
			  	
				<div class="card">
				   
					<img src="./images/NouveauDocument.png" width="100%" height='270px'>

					<div class="contenu">
						<h3><b><u>Nouveau Probléme</u></b></h3><br>
						<h4><p>Entrer Un Nouveau Probleme détecté<p></h4><br>
					    <a href="insertion.php" class="btn1" align="center">Ajouter</a>
					</div>
					
				</div>

            </div>	
                <div class="box"> 
					
				    <div class="card">
					   <img src="./images/images.jpeg" width="100%" height="270px">

						<div class="contenu">
						   <h3><b><u>Bilan des pannes</u></b></h3><br>
						    <h4><p>Voir le compte-rendu saisi</p></h4><br>
                    	   <a href="consultation.php" class="btn2" align="center"><span class="glyphicon glyphicon-eye-open" ></span>Voir</a>
						</div>
					</div>	
				</div>	
			</div>
        </div>			
	    <!--fin du corps de la page-->	
	    <!--pieds de page-->
		<div class="piedsdepage">
			         <h2>©2020</h2>
	    </div>
		<!--fin du pieds de page-->		
		


		<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="./js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
</body>
</html>
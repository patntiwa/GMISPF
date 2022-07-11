<?php
// define variables and set to empty values
$UsernameErr = $mdpErr = $cnfmdpErr ="";
$Username = $mdp = $cnfmdp ='';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
 		 if (empty($_POST["Username"])) {
    		$UsernameErr = "Ce champ est Obligatoire";
  		}
  
  		if (empty($_POST["mdp"])) {
  		  $mdpErr = "Un mot de passe est necessaire";
  		} 
    
  		if (empty($_POST["cnfmdp"])) {
  		  $cnfmdpErr = "Un mot de passe est necessaire";
  		} 

}

?>
<!DOCTYPE html>
<html>
<meta charset="utf-8">
<head>

<link rel="stylesheet" type="text/css" href="./css/StylePage.css">

<link rel="shortcut icon" type="images/x-icon" href="./images/beac-logo.ico">

<link type="text/css" rel="stylesheet" href="./css/all.css" media="all">

<style>
      .error {color: #FF0000;}
</style>

	<title> GMISPF Inscription  </title>
</head>
<body>
  <!--Corps de la page -->

	   <!--fond de la page -->

<div style="background-image: url(./images/IMG_20190903_153008.jpg);background-size:cover; height:50%; width: 100%;" >	
	   <!--fin du background -->

	   <!-- DEbut du formulaire -->
        <div class="corps">
				<form method="post"  action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
		 			      <h2 align="center">Formulaire d'inscription</h2><br>
			        <table class="tableau">
						
							<tr><td><label for="username">Nom D'utilisateur:</label> <span class="error">* <?php echo $UsernameErr;?></span></td></tr>
						
							<tr><td><input type="text" name="Username" placeholder="Entrez votre Nom d'utilisateur" value="<?php echo $Username ?>" required/></td>
			
							<tr><td><label for="fonction">Fonction</label></td></tr>
						
							<tr><td>	
								<select name="Fonction">
									<option value="Agent">Agent</option>
									<option value="Prestataire">Prestataire</option>
								</select>
								</td></tr>
								<tr><td><label for="password">Creer un mot de passe</label><span class="error">*<?php echo $mdpErr;?></span></td></tr>
						
								<tr><td><input type="password" name="mdp" placeholder="Entrez votre Mot de Passe" required/></td></tr>
					
								<tr><td><label for="password">Confirmer Votre mot de passe</label><span class="error">* <?php echo $cnfmdpErr;?></span>
						
								<tr><td><input type="password" name="cnfmdp" placeholder="Confirmez votre Mot de Passe" required/></td></tr>

								<tr><td><input type="submit" name="send" value="Valider l'inscription"></td></tr>
						
							</table>	    
    			</form>
         </div>
<!-- fin du  formulaire-->
<?php
  
  if (isset($_POST['send']) and $_POST['mdp'] == $_POST['cnfmdp'])
  {
		//RECUPERATION DES VALEURS DU FORMULAIRE
		            $Username=$_POST['Username'];
					$Email=$_POST['Email'];
					$sexe=$_POST['sexe'];
					$Fonction=$_POST['Fonction'];
					$mdp=$_POST['mdp'];
		//FIN DE RECUPERATION
	
		
					//CONNEXION A LA BD
		 
					include "./Fonction/connexion.php";
		
					//REQUETE POUR L INSERTION
		
					$sql="INSERT INTO utilisateur(Username,Email,Fonction,mdp) VALUES('$Username','$Email','$Fonction','$mdp')";
	
	   
			$res=mysqli_query($base,$sql);
			
			//SI LA REQUETTE EST VALIDE
			 
			if ($res == TRUE) {
?>            
	 
	<script language="javascript"> window.alert('Votre Compte a ete Creer Dans la base de donnée');</script>   

<?php           
			 //REDIRIGE VERS INDEX
			
			 header("location:index.php");
			  
			}
			 
			  else{
				
				//SINON RESTE SUR INSCRIPTION
			   
				header("location:inscription.php");
			  
			}
   
}
?>         
</div>

 
</body>
</html>
<?php 

	// require_once 'DbConnect.php';
	
	$servername = "localhost";
	$username = "root";
	$password = "";
	$database = "crc_project";
	 
	 
	//creating a new connection object using mysqli 
	$conn = new mysqli($servername, $username, $password, $database);
	 
	//if there is some error connecting to the database
	//with die we will stop the further execution by displaying a message causing the error 
	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	}
	
	$response = array();
	  //if it is an api call 
	 //that means a get parameter named api call is set in the URL 
	 //and with this parameter we are concluding that it is an api call 

	function isTheseParametersAvailable($params){
		//function validating all the paramters are available
		foreach($params as $param){
			if(!isset($_POST[$param])){
				return false; 
			}
		}
		//return true if every param is available 
		return true; 
	}
	
	if(isset($_GET['api_call'])){
		
		switch($_GET['api_call']){
			
			case 'signup':
				if(isTheseParametersAvailable(array('username','email','password','gender'))){
					$username = $_POST['username']; 
					$email = $_POST['email']; 
					$password = md5($_POST['password']);
					$gender = $_POST['gender']; 
					
					//checking if the user is already exist with this username or email
 					//as the email and username should be unique for every user 
					$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
					$stmt->bind_param("ss", $username, $email); 
					/* 
						The argument may be one of four types: i - integer, d - double, s - string, b - BLOB
					*/
					$stmt->execute();
					$stmt->store_result();
					//if the user already exist in the database
					if($stmt->num_rows > 0){
						$response['error'] = true;
						$response['message'] = 'Username already registered';
						$stmt->close();
						//$conn->close();
					}else{

						//if user is new creating an insert query
						$stmt = $conn->prepare("INSERT INTO users (username, email, password, gender) VALUES (?, ?, ?, ?)");
						$stmt->bind_param("ssss", $username, $email, $password, $gender);

						//if the user is successfully added to the database
						if($stmt->execute()){
							//fetching the user back 
							$stmt = $conn->prepare("SELECT id, id, username, email, gender FROM users WHERE username = ?"); 
							$stmt->bind_param("s",$username);
							$stmt->execute();
							$stmt->bind_result($userid, $id, $username, $email, $gender);
							$stmt->fetch();
							
							$user = array(
								'id'=>$id, 
								'username'=>$username, 
								'email'=>$email,
								'gender'=>$gender
							);
							
							$stmt->close();
							//$conn->close();
							//adding the user data in response 
							$response['error'] = false; 
							$response['message'] = 'User registered successfully'; 
							$response['user'] = $user; 
						}
					}
					
				}else{
					$response['error'] = true; 
					$response['message'] = 'required parameters are not available'; 
				}
				
			break; 
			
			case 'login':
				
				if(isTheseParametersAvailable(array('username', 'password'))){
					
					$username = $_POST['username'];
					$password = md5($_POST['password']); 
					
					$stmt = $conn->prepare("SELECT id, username, email, gender FROM users WHERE username = ? AND password = ?");
					$stmt->bind_param("ss",$username, $password);
					$stmt->execute();
					$stmt->store_result();
					
					if($stmt->num_rows > 0){
						
						$stmt->bind_result($id, $username, $email, $gender);
						$stmt->fetch();
						
						$user = array(
							'id'=>$id, 
							'username'=>$username, 
							'email'=>$email,
							'gender'=>$gender
						);
						
						$response['error'] = false; 
						$response['message'] = 'Login Success';
						$response['user'] = $user; 
					}else{
						$response['error'] = false; 
						$response['message'] = 'Invalid username or password';
					}
				}
			break; 
			
			default: 
				$response['error'] = true; 
				$response['message'] = 'Invalid Operation Called';
		}
		
	}else{
		 //if it is not an api call 
 		//pushing appropriate values to the response array
		$response['error'] = true; 
		$response['message'] = 'Invalid API Call';
	}
	//displaying the response in JSON 
	echo json_encode($response);
	

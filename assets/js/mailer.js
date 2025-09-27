function checkmail(input)
		 {
			var pattern1=/^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
			if(pattern1.test(input)){ return true; }else{ return false; }
		 }     
		  function proceed(){
			 var name = document.getElementById("name");
			 var email = document.getElementById("email");
			 var msg = document.getElementById("message");
			 var errors = "";
			 
			 if(name.value == "")
			  { 
			  name.className = 'error';
				return false;
			  }
				
			  else if(email.value == "")
			  {
				email.className = 'error';
				return false;
			  }
			  else if(checkmail(email.value)==false)
			  {
				alert('Please provide a valid email address.');
				return false;
		
			  }
			  else if(msg.value == "")
			  {
				msg.className = 'error';
				return false;
			  }
			  else 
				{
					grecaptcha.ready(function() {
						grecaptcha.execute('6LfYTZklAAAAAOGl2dRok8sKj_o8VEPEELJJmTKA', {action: 'subscribe_newsletter'}).then(function(token) {
							console.log('ready');
							$('#contact_form').prepend('<input type="hidden" name="token" value="' + token + '">');
							$('#contact_form').prepend('<input type="hidden" name="action" value="subscribe_newsletter">');

							$.ajax({
								type: "POST",
								url: "process.php",
								data: $("#contact_form").serialize(),
								success: function(msg)
								{
									//alert(msg);
									if(msg == 'success'){
										$('#contact_form').fadeOut(1000);
										$('#contact_message').fadeIn(2000);
										document.getElementById("contact_message").innerHTML = "Your message has been sent. We will get back to you ASAP.";
										return true;
									}else{

										$('#contact_form').fadeOut(500);
										$('#contact_message').fadeIn(2000);
                                                                                document.getElementById("contact_message").innerHTML = "Oops, your message hasn't been sent. Try later or contact hello@mtdashboard.example.";
										return true;
									}
								}
							});
						});
					});
				} 
		  }


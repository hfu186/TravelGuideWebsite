document.addEventListener("DOMContentLoaded", function () {

    function showLoginSignup() {
      const container = document.getElementById("container");
      container.style.display = "block"; 
    }
  
    setTimeout(showLoginSignup, 5000); // 
  
    const signUpButton = document.getElementById('signUp');
    const signInButton = document.getElementById('signIn');
    const container = document.getElementById('container');
  
    signUpButton.addEventListener('click', () =>
      container.classList.add('right-panel-active'));
  
    signInButton.addEventListener('click', () =>
      container.classList.remove('right-panel-active'));
  });
  
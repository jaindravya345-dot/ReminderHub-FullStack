let csrfToken = "";
const errorBox = document.getElementById("form-error");
async function initAuth(){try{const r=await fetch("api/auth.php");const d=await r.json();csrfToken=d.csrfToken||"";}catch{errorBox.textContent="Cannot reach the server. Make sure Apache is running.";}}
document.getElementById("auth-form").addEventListener("submit",async e=>{
 e.preventDefault();errorBox.textContent="";
 const isRegister=!!document.getElementById("name");
 const payload={action:isRegister?"register":"login",email:document.getElementById("email").value,password:document.getElementById("password").value};
 if(isRegister)payload.name=document.getElementById("name").value;
 const button=e.currentTarget.querySelector("button[type=submit]");button.disabled=true;button.textContent="Please wait...";
 try{const r=await fetch("api/auth.php",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-Token":csrfToken},body:JSON.stringify(payload)});const d=await r.json();if(!r.ok||!d.ok)throw new Error(d.error||"Something went wrong.");window.location.href="index.php";}
 catch(err){errorBox.textContent=err.message;button.disabled=false;button.innerHTML=isRegister?'Create account <span>→</span>':'Sign in <span>→</span>';}
});
initAuth();

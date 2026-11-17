export function getCookie(cookieName) {let name=cookieName+"=";let ca=document.cookie.split(";");for(let i=0;i<ca.length;i++){let c=ca[i];while(c.charAt(0)==" "){c=c.substring(1);}if(c.indexOf(name)==0){return decodeURIComponent(c.substring(name.length,c.length));}}return"";}

export function setCookie(cookieName, cookieValue, cookieExpireSeconds = 180 * 24 * 60 * 60) {let d=new Date();d.setTime(d.getTime()+(cookieExpireSeconds*1000)); let expires="expires="+d.toUTCString();document.cookie=cookieName+"="+encodeURIComponent(cookieValue)+";"+expires+";path=/";}

export function existsCookie(cookieName) {let name=cookieName+"=";let ca=document.cookie.split(";");for(let i=0;i<ca.length;i++){let c=ca[i];while(c.charAt(0)==" "){c=c.substring(1);}if(c.indexOf(name)==0){return true;}}return false;}

export function removeCookie(cookieName) {setCookie(cookieName, "", -1);}

export function listCookies() {let ca=document.cookie.split(";");let cookieNames=[];for(let i=0;i<ca.length;i++){let c=ca[i];while(c.charAt(0)==" "){c=c.substring(1);}let cookieName=c.split("=")[0];if(cookieName){cookieNames.push(cookieName);}}return cookieNames;}

export function clearAllStorage() {localStorage.clear();sessionStorage.clear();for(let cookieName of listCookies()){removeCookie(cookieName);}}

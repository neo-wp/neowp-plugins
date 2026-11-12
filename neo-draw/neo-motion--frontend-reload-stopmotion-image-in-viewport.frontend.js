// Find the original unminified code in neo-motion--frontend-reload-stopmotion-image-in-viewport.frontend.original.js
(async()=>{function hashStringForObserver(str){const hashDjb2=(str)=>{let hash=5381;for(let i=0;i<str.length;i++){hash=((hash<<5)+hash)+str.charCodeAt(i)}hash=Math.abs(hash).toString(16).padStart(8,"0");return hash};const hash1=hashDjb2(str);const hash2=hashDjb2(hash1+str);return hash1+hash2}
function observeOnce(selector,callback,{domRoot=document.documentElement,callbackSalt=""}={}){if(!callback){return new Promise(resolve=>observeOnce(selector,resolve,{domRoot,callbackSalt:Math.random().toString()}))}
const uniqueKey=hashStringForObserver(`${selector}|${callback.toString()}|${callbackSalt}`);observeOnce.observerRegistries??=new WeakMap();if(!observeOnce.observerRegistries.has(domRoot)){observeOnce.observerRegistries.set(domRoot,[])}
if(observeOnce.observerRegistries.get(domRoot).some(entry=>entry.uniqueKey===uniqueKey)){return}
const newObserverEntry={uniqueKey,selector,callback,domRoot,processedNodes:new Set()};observeOnce.observerRegistries.get(domRoot).push(newObserverEntry);for(const node of domRoot.querySelectorAll(selector)){newObserverEntry.processedNodes.add(node);callback(node)}
observeOnce.observersExistingForDomRoots??=new WeakSet();if(observeOnce.observersExistingForDomRoots.has(domRoot)){return}observeOnce.observersExistingForDomRoots.add(domRoot);new MutationObserver(async mutations=>{observeOnce.semaphores??=new WeakMap();observeOnce.semaphores.set(domRoot,!0);await new Promise(requestAnimationFrame);if(!observeOnce.semaphores.get(domRoot)){return}observeOnce.semaphores.set(domRoot,!1);for(const registryEntry of observeOnce.observerRegistries.get(domRoot)){for(const node of domRoot.querySelectorAll(registryEntry.selector)){if(registryEntry.processedNodes.has(node)){continue}
registryEntry.processedNodes.add(node);registryEntry.callback(node)}}}).observe(domRoot,{childList:!0,subtree:!0})}
function observeInViewportOnce(node,callback,rootMargin="0px"){if(typeof node==="string"){observeOnce(node,(node)=>{observeInViewportOnce(node,callback,rootMargin)},{callbackSalt:hashStringForObserver(callback.toString())});return}
new IntersectionObserver((entries,observer)=>{entries.forEach(entry=>{if(entry.isIntersecting){callback(entry.target);observer.disconnect()}})},{rootMargin:rootMargin}).observe(node)}
function observeAttributes(node,callback){if(typeof node==="string"){observeOnce(node,(node)=>{observeAttributes(node,callback)},{callbackSalt:hashStringForObserver(callback.toString())});return}
new MutationObserver(mutations=>{callback(node)}).observe(node,{attributes:!0,childList:!1,subtree:!1,characterData:!1});callback(node)}
function observeNode(node,callback){if(typeof node==="string"){observeOnce(node,(node)=>{observeNode(node,callback)},{callbackSalt:hashStringForObserver(callback.toString())});return}
new MutationObserver(mutations=>{callback(node)}).observe(node,{attributes:!0,childList:!0,subtree:!0,characterData:!0});callback(node)}
function observeResize(node,callback){if(typeof node==="string"){observeOnce(node,(node)=>{observeResize(node,callback)},{callbackSalt:hashStringForObserver(callback.toString())});return}
let nodeWidth,nodeHeight;new ResizeObserver((entries)=>{entries.forEach(async(entry)=>{const{width,height}=entry.contentRect;if(nodeWidth===width&&nodeHeight===height){return}
nodeWidth=width;nodeHeight=height;callback(node,{width,height});await new Promise(requestAnimationFrame);while(entry.target.getAnimations().some(a=>a.playState==="running")){const boundingRect=entry.target.getBoundingClientRect();if(nodeWidth===boundingRect.width&&nodeHeight===boundingRect.height){break}
nodeWidth=boundingRect.width;nodeHeight=boundingRect.height;callback(entry.target,{width:boundingRect.width,height:boundingRect.height});await new Promise(requestAnimationFrame)}})}).observe(node)}
function observeClick(node,callback){if(typeof node==="string"){observeOnce(node,(node)=>{observeClick(node,callback)},{callbackSalt:hashStringForObserver(callback.toString())});return}
node.addEventListener("click",(event)=>callback(node,event))}
function _addEventListenerWithObserverSupport(node,eventType,callback){if(typeof node==="string"){observeOnce(node,(node)=>{_addEventListenerWithObserverSupport(node,eventType,callback)},{callbackSalt:hashStringForObserver(callback.toString())});return}
node.addEventListener(eventType,callback)}
function addEventListenerWithInitialCall(node,eventType,callback){_addEventListenerWithObserverSupport(node,eventType,callback);callback({target:node})}
function addEventListenerWithInitialCallMultiple(nodesAndEventTypes,callback){for(const[node,eventType]of nodesAndEventTypes){_addEventListenerWithObserverSupport(node,eventType,callback)}
callback()}
async function domLoaded(callback){if(document.readyState==="loading"){await new Promise(resolve=>window.addEventListener("DOMContentLoaded",resolve))}
if(callback){callback()}}
async function delay(time,callback){await domLoaded();await new Promise(resolve=>setTimeout(resolve,time*1000));if(callback){callback()}}
observeAttributes("img",async img=>{if(!img.getAttribute("src")?.includes("neo-motion=true")){return}
if(img.neoMotionIntersectionObserver){return}
new IntersectionObserver(async entries=>{for(const entry of entries){if(!entry.isIntersecting){continue}
const{isSafari}=await import("./_global--detect-safari.js");if(!isSafari()){img.src=img.src.split("#")[0]+"#"+Date.now()}else{if(img.src.startsWith("data:image/svg+xml;base64,")){let decodedBase64=atob(img.src.split(',')[1]);decodedBase64+=" ";img.src="data:image/svg+xml;base64,"+btoa(decodedBase64)}else{await new Promise(requestAnimationFrame);const reader=new FileReader();reader.readAsDataURL(await(await fetch(img.src)).blob());await new Promise(resolve=>reader.onloadend=resolve);img.src=reader.result}}}},{rootMargin:"0px"}).observe(img);img.neoMotionIntersectionObserver=!0})})()
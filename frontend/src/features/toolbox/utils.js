export function formatJson(value){const parsed=JSON.parse(value);return JSON.stringify(parsed,null,2)}
export function minifyJson(value){return JSON.stringify(JSON.parse(value))}

export function encodeBase64(value){const bytes=new TextEncoder().encode(value);let binary='';bytes.forEach(byte=>{binary+=String.fromCharCode(byte)});return btoa(binary)}
export function decodeBase64(value){const normalized=value.trim().replace(/\s/g,'');if(!normalized) return '';if(!/^[A-Za-z0-9+/]*={0,2}$/.test(normalized)||normalized.length%4===1)throw new Error('Invalid Base64 input.');const binary=atob(normalized);const bytes=Uint8Array.from(binary,c=>c.charCodeAt(0));return new TextDecoder('utf-8',{fatal:true}).decode(bytes)}
export const encodeUrl=value=>encodeURIComponent(value);
export const decodeUrl=value=>decodeURIComponent(value);

export function timestampDetails(value,unit='auto'){
 const raw=String(value).trim();if(!/^-?\d+(\.\d+)?$/.test(raw))throw new Error('Enter a valid Unix timestamp.');
 const number=Number(raw);if(!Number.isFinite(number))throw new Error('Timestamp is outside the supported range.');
 const resolved=unit==='auto'?(Math.abs(number)>=1e11?'milliseconds':'seconds'):unit;
 const date=new Date(resolved==='seconds'?number*1000:number);if(Number.isNaN(date.getTime()))throw new Error('Timestamp is outside the supported date range.');
 return {unit:resolved,iso:date.toISOString(),utc:date.toUTCString(),local:date.toLocaleString(),seconds:Math.trunc(date.getTime()/1000),milliseconds:date.getTime()};
}

export function generateUuids(count=1){const safe=Math.max(1,Math.min(20,Number(count)||1));if(!globalThis.crypto?.randomUUID)throw new Error('Secure UUID generation is not available in this browser.');return Array.from({length:safe},()=>globalThis.crypto.randomUUID())}

export async function sha256(value){if(!globalThis.crypto?.subtle)throw new Error('Secure hashing is not available in this browser.');const digest=await globalThis.crypto.subtle.digest('SHA-256',new TextEncoder().encode(value));return Array.from(new Uint8Array(digest),b=>b.toString(16).padStart(2,'0')).join('')}

function decodeBase64Url(segment){const base64=segment.replace(/-/g,'+').replace(/_/g,'/').padEnd(Math.ceil(segment.length/4)*4,'=');return decodeBase64(base64)}
export function inspectJwt(token){
 const value=token.trim();const parts=value.split('.');if(parts.length!==3)throw new Error('A JWT must contain exactly three dot-separated parts.');
 let header,payload;try{header=JSON.parse(decodeBase64Url(parts[0]));payload=JSON.parse(decodeBase64Url(parts[1]))}catch{throw new Error('JWT header or payload is not valid Base64URL JSON.')}
 const claims={};['iat','nbf','exp'].forEach(key=>{if(typeof payload[key]==='number'){try{claims[key]=timestampDetails(payload[key],'seconds')}catch{claims[key]=null}}});
 return {header,payload,claims};
}

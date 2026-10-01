(() => {
const log=document.getElementById('chat-messages');const status=document.getElementById('chat-sync');
if(!log)return;
log.scrollTop=log.scrollHeight;
if(log.dataset.live!=='true')return;
let busy=false;
async function update(){
 if(busy||document.hidden)return;busy=true;
 try{
 const response=await fetch(log.dataset.feed,{headers:{'Accept':'application/json'},credentials:'same-origin',cache:'no-store'});
 if(response.status===401||response.status===403||response.status===404){status.textContent='Akses percakapan berakhir. Muat ulang halaman untuk masuk kembali.';clearInterval(timer);return;}
 if(!response.ok)throw new Error('unavailable');
 const data=await response.json();
 if(String(data.latest_id)!==log.dataset.latest){const atBottom=log.scrollHeight-log.scrollTop-log.clientHeight<100;log.innerHTML=data.html;log.dataset.latest=String(data.latest_id);if(atBottom)log.scrollTop=log.scrollHeight;status.textContent=atBottom?'Pesan terbaru ditampilkan.':'Ada pesan baru. Gulir ke bawah untuk membacanya.';}
 else status.textContent='Pesan baru diperiksa otomatis setiap 8 detik saat halaman terbuka.';
 }catch{status.textContent='Koneksi terputus. Mencoba kembali; pesan yang sedang diketik tetap tersimpan di formulir.';}
 finally{busy=false;}
}
const timer=setInterval(update,8000);
})();

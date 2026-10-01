(() => {
const body=document.getElementById('notification-message');const feedback=document.getElementById('notification-feedback');
if(!body)return;
document.getElementById('open-whatsapp')?.addEventListener('click',event=>{
 const phone=event.currentTarget.dataset.phone;if(!phone||!body.value.trim())return;
 window.open('https://wa.me/'+phone+'?text='+encodeURIComponent(body.value),'_blank','noopener,noreferrer');feedback.textContent='Tekan Kirim di WhatsApp. Catat pengiriman setelah pesan benar-benar Anda kirim.';
});
document.getElementById('copy-notification')?.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(body.value);feedback.textContent='Pesan disalin.';}catch{body.focus();body.select();feedback.textContent='Pilih teks pesan dan salin secara manual.';}});
document.getElementById('download-status-card')?.addEventListener('click',()=>{
 const data=JSON.parse(document.getElementById('status-card').dataset.card);
 const canvas=document.createElement('canvas');canvas.width=1200;canvas.height=3000;const ctx=canvas.getContext('2d');
 ctx.fillStyle='#f0f5f2';ctx.fillRect(0,0,1200,3000);ctx.fillStyle='#ffffff';ctx.fillRect(60,60,1080,2880);
 ctx.fillStyle='#164a46';ctx.fillRect(60,60,1080,250);ctx.fillStyle='#ffffff';ctx.font='bold 56px sans-serif';ctx.fillText('IZZAN',110,150);ctx.font='28px sans-serif';ctx.fillText('DIGITAL PRINTING',110,200);ctx.fillText('KARTU STATUS PESANAN',110,265);
 function wrap(text,x,y,width,size=30,color='#26453f',bold=false){ctx.fillStyle=color;ctx.font=(bold?'bold ':'')+size+'px sans-serif';let line='';for(const word of String(text).split(/\s+/)){if(ctx.measureText(line+word).width>width&&line){ctx.fillText(line,x,y);y+=size*1.45;line='';}if(ctx.measureText(word).width>width){if(line){ctx.fillText(line,x,y);y+=size*1.45;line='';}let chunk='';for(const char of word){if(ctx.measureText(chunk+char).width>width){ctx.fillText(chunk,x,y);y+=size*1.45;chunk='';}chunk+=char;}line=chunk+' ';}else line+=word+' ';}if(line){ctx.fillText(line,x,y);y+=size*1.45;}return y;}
 let y=wrap(data.number,110,380,960,38,'#164a46',true);y=wrap(data.customer,110,y+15,960,30);y=wrap(data.service,110,y+10,960,32,'#26453f',true);
 y=wrap(data.status,110,y+40,960,38,'#167560',true);y=wrap(data.action,110,y+15,960,28);
 const rows=[['Jumlah / volume',data.quantity+' · '+data.volume],['Ukuran',data.size],['Penyerahan',data.delivery],['Harga final',data.final],['Diverifikasi',data.verified],['Sisa tagihan',data.remaining],['Pembayaran',data.payment]];
 for(const [label,value] of rows){ctx.strokeStyle='#e1eae5';ctx.beginPath();ctx.moveTo(110,y+8);ctx.lineTo(1090,y+8);ctx.stroke();y=wrap(label,110,y+45,320,25,'#71817b');const end=wrap(value,440,y-36,650,30,'#26453f',true);y=Math.max(y,end)+16;}
 ctx.fillStyle='#fff6e6';ctx.fillRect(100,y+25,1000,75);ctx.fillStyle='#886d30';ctx.font='22px sans-serif';ctx.fillText('SIMULASI · Bukan bukti transaksi nyata',125,y+72);ctx.fillStyle='#71817b';ctx.font='22px sans-serif';ctx.fillText('Data saat halaman dibuka: '+data.date,110,y+155);
 const output=document.createElement('canvas');output.width=1200;output.height=Math.ceil(y+230);output.getContext('2d').drawImage(canvas,0,0);
 output.toBlob(blob=>{if(!blob){feedback.textContent='Kartu belum bisa diunduh. Gunakan cetak nota.';return;}const url=URL.createObjectURL(blob);const link=document.createElement('a');link.href=url;link.download=data.number+'-status.jpg';link.click();setTimeout(()=>URL.revokeObjectURL(url),5000);feedback.textContent='Kartu JPG diunduh. Lampirkan manual di WhatsApp.';},'image/jpeg',0.92);
});
})();

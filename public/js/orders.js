const service=document.getElementById('service_id');
const quantity=document.getElementById('quantity');
const width=document.getElementById('width');
const height=document.getElementById('height');
function updateOrder(){
 const choice=service.selectedOptions[0], unit=choice.dataset.unit, minimum=Number(choice.dataset.min||1);
 quantity.min=minimum;
 document.getElementById('quantity-hint').textContent=`Minimal ${minimum}. ${['m','m2'].includes(unit)?'Jumlah barang, bukan luas. Ukuran diisi per barang.':'Jumlah sesuai satuan layanan.'}`;
 document.getElementById('width-field').hidden=!['m','m2'].includes(unit);width.disabled=!['m','m2'].includes(unit);width.required=['m','m2'].includes(unit);
 document.getElementById('height-field').hidden=unit!=='m2';height.disabled=unit!=='m2';height.required=unit==='m2';
 const q=Number(quantity.value), w=Number(width.value), h=Number(height.value);
 const volume=unit==='m2'?q*w*h:unit==='m'?q*w:q;
 const valid=Boolean(unit)&&Number.isInteger(q)&&q>=minimum&&q<=1000000&&volume>0&&(!['m','m2'].includes(unit)||(w>0&&w<=1000))&&(unit!=='m2'||(h>0&&h<=1000));
 document.getElementById('estimate-volume').textContent=valid?`${volume.toLocaleString('id-ID',{maximumFractionDigits:4})} ${unit==='m2'?'m²':unit}`:'Lengkapi jumlah dan ukuran';
 document.getElementById('estimate-price').textContent=valid&&choice.dataset.price!==''&&choice.dataset.price!==undefined?(volume*Number(choice.dataset.price)).toLocaleString('id-ID',{style:'currency',currency:'IDR'}):'Menunggu pemeriksaan';
}
[service,quantity,width,height].forEach(el=>el?.addEventListener('input',updateOrder));
const delivery=document.getElementById('delivery_method');
function updateDelivery(){const shipping=delivery.value==='shipping';document.getElementById('address-field').hidden=!shipping;const address=document.getElementById('delivery_address');address.disabled=!shipping;address.required=shipping;}
delivery?.addEventListener('change',updateDelivery);
const designMode=document.getElementById('design_mode');
function updateDesign(){document.getElementById('design').required=designMode.value==='upload';document.getElementById('customer_notes').required=designMode.value==='assistance';}
designMode?.addEventListener('change',updateDesign);
if(service){updateOrder();updateDelivery();updateDesign();}

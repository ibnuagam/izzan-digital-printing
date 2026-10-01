const toggle = document.getElementById('password-toggle');
const password = document.getElementById('password');
toggle?.addEventListener('click', () => {
    const visible = password.type === 'password';
    password.type = visible ? 'text' : 'password';
    toggle.textContent = visible ? 'Sembunyikan' : 'Tampilkan';
    toggle.setAttribute('aria-pressed', String(visible));
});
const menu = document.getElementById('menu-toggle');
const overlay = document.getElementById('sidebar-overlay');
function setMenu(open) {
    document.body.classList.toggle('menu-open', open);
    menu?.setAttribute('aria-expanded', String(open));
    if (overlay) overlay.hidden = !open;
    if (open) document.querySelector('#sidebar a')?.focus();
    else menu?.focus();
}
menu?.addEventListener('click', () => setMenu(!document.body.classList.contains('menu-open')));
overlay?.addEventListener('click', () => setMenu(false));
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && document.body.classList.contains('menu-open')) setMenu(false);
});
window.matchMedia('(min-width: 901px)').addEventListener('change', (event) => {
    if (event.matches && document.body.classList.contains('menu-open')) setMenu(false);
});

const imageInput = document.getElementById('image');
const imagePreview = document.getElementById('image-preview');
const imagePlaceholder = document.getElementById('image-placeholder');
const imageStatus = document.getElementById('image-preview-status');
const originalImage = imagePreview?.getAttribute('src') || '';
let previewUrl;
imageInput?.addEventListener('change', () => {
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    const file = imageInput.files[0];
    if (!file) {
        imagePreview.src = originalImage;
        imagePreview.hidden = !originalImage;
        imagePlaceholder.hidden = Boolean(originalImage);
        imageStatus.textContent = '';
        return;
    }
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) {
        imageStatus.textContent = 'Pilih JPG, PNG, atau WebP maksimal 2 MB.';
        imageInput.value = '';
        imagePreview.src = originalImage;
        imagePreview.hidden = !originalImage;
        imagePlaceholder.hidden = Boolean(originalImage);
        return;
    }
    previewUrl = URL.createObjectURL(file);
    imagePreview.src = previewUrl;
    imagePreview.hidden = false;
    imagePlaceholder.hidden = true;
    imageStatus.textContent = 'Pratinjau tersedia. Gambar disimpan setelah Anda menekan Simpan layanan.';
});

document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{if(!window.confirm(form.dataset.confirm))event.preventDefault();}));

const paymentMethod=document.getElementById('method');
function showPaymentMethod(){document.querySelectorAll('[data-payment-method]').forEach(panel=>panel.hidden=panel.dataset.paymentMethod!==paymentMethod.value);}
paymentMethod?.addEventListener('change',showPaymentMethod);if(paymentMethod)showPaymentMethod();

function rupiahValue(value){
 let text=value.trim().replace(/^Rp\s*/i,'');
 if(/^\d+$/.test(text))return text;
 if(/^\d{1,3}(?:\.\d{3})+(?:,\d{1,2})?$/.test(text))return text.replace(/\./g,'').replace(',','.');
 if(/^\d{1,3}(?:,\d{3})+(?:\.\d{1,2})?$/.test(text))return text.replace(/,/g,'');
 if(/^\d+[,.]\d{1,2}$/.test(text))return text.replace(',','.');
 return null;
}
document.querySelectorAll('[data-rupiah]').forEach(input=>{
 const preview=document.createElement('p');preview.className='rupiah-preview';preview.id=input.id+'-preview';preview.setAttribute('aria-live','polite');input.insertAdjacentElement('afterend',preview);input.setAttribute('aria-describedby',[input.getAttribute('aria-describedby'),preview.id].filter(Boolean).join(' '));
 const update=()=>{const value=rupiahValue(input.value);const invalid=input.value.trim()!==''&&value===null;input.setCustomValidity(invalid?'Gunakan 250000, 250.000, atau 250.000,00. Maksimal dua angka desimal.':'');preview.classList.toggle('invalid',invalid);preview.textContent=invalid?'Format belum valid. Contoh Rp250.000: 250000 atau 250.000,00.':value===null?'':new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:2}).format(Number(value));};input.addEventListener('input',update);update();
});

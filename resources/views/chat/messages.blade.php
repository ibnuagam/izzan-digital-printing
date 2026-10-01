@forelse($messages->getCollection()->reverse() as $message)
@php $mine=$message->user_id===auth()->id(); @endphp
<article class="chat-message {{ $mine?'chat-mine':'chat-other' }}"><div class="chat-bubble"><strong>{{ $message->sender_role==='admin'?'Admin · ':'Pelanggan · ' }}{{ $message->user->name }}</strong><p>{{ $message->body }}</p><time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</time></div></article>
@empty<div class="chat-empty"><x-icon name="chat"/><h2>Mulai percakapan</h2><p>Tulis pertanyaan tentang desain, negosiasi harga, atau pembayaran pesanan ini.</p></div>@endforelse

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderChatController extends Controller
{
    private function authorizeOrder(Order $order): void
    {
        $user = auth()->user();
        abort_unless($user->role === 'admin' || ($user->role === 'pelanggan' && $order->user_id === $user->id), 404);
    }

    public function index(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:150']]);
        $query = Order::with('user', 'latestMessage.user')->withMax('messages', 'id');
        if ($request->user()->role === 'pelanggan') {
            $query->where('user_id', $request->user()->id);
        }
        if ($q = trim($data['q'] ?? '')) {
            $query->where(function ($query) use ($q) {
                $query->where('number', 'like', '%'.$q.'%')->orWhere('service_name', 'like', '%'.$q.'%');
            });
        }

        return view('chat.index', ['orders' => $query->orderByDesc('messages_max_id')->orderByDesc('id')->paginate(15)->withQueryString()]);
    }

    public function show(Request $request, Order $order)
    {
        $this->authorizeOrder($order);
        $request->validate(['page' => ['nullable', 'integer', 'min:1', 'max:100000']]);
        $messages = $order->messages()->with('user')->orderByDesc('id')->paginate(50);

        return view('chat.show', ['order' => $order->load('user'), 'messages' => $messages]);
    }

    public function feed(Order $order)
    {
        $this->authorizeOrder($order);
        $messages = $order->messages()->with('user')->orderByDesc('id')->paginate(50, ['*'], 'page', 1);

        return response()->json(['html' => view('chat.messages', ['messages' => $messages])->render(), 'latest_id' => $messages->first()?->id ?? 0])->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request, Order $order)
    {
        $this->authorizeOrder($order);
        if (is_string($request->input('body'))) {
            $request->merge(['body' => trim($request->input('body'))]);
        }
        $data = $request->validate(['body' => ['required', 'string', 'max:3000'], 'submission_key' => ['required', 'uuid']]);
        DB::transaction(function () use ($order, $data, $request) {
            Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $existing = OrderMessage::where('submission_key', $data['submission_key'])->first();
            if ($existing) {
                abort_unless($existing->order_id === $order->id && $existing->user_id === $request->user()->id, 409);

                return;
            }
            $order->messages()->create(['user_id' => $request->user()->id, 'sender_role' => $request->user()->role, 'body' => $data['body'], 'submission_key' => $data['submission_key']]);
        });

        return redirect()->route($request->user()->role.'.chat.show', $order)->with('status', 'Pesan terkirim.');
    }
}

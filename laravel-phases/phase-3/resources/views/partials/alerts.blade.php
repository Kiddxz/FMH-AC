{{-- Messages on the login / register / password pages:
     green = success (session 'status'), red = form errors ($errors). --}}
@if (session('status'))
    <div style="margin: 0 0 18px; padding: 12px 16px; border-radius: 10px; background: #e6f5e9; border: 1px solid #a8d5b5; color: #1f6b3d; font-size: 15px; text-align: center;">✅ {{ session('status') }}</div>
@endif
@if ($errors->any())
    <div style="margin: 0 0 18px; padding: 12px 16px; border-radius: 10px; background: #ffe8e8; border: 1px solid #f3b4b4; color: #a12b2b; font-size: 15px;">
        @foreach ($errors->all() as $error)
            <div>⚠️ {{ $error }}</div>
        @endforeach
    </div>
@endif

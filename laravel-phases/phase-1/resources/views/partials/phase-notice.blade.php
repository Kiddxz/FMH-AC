{{-- Temporary message shown after submitting a form that is not connected to the database yet. --}}
@if (session('phase_notice'))
    <div style="max-width: 900px; margin: 16px auto; padding: 12px 16px; border-radius: 10px; background: #fff4e5; border: 1px solid #f5c58a; color: #7a4b00; font-size: 15px; text-align: center;">
        ℹ️ {{ session('phase_notice') }}
    </div>
@endif

<x-mail::message>
# <center>Upcoming Appointment Reminder</center>

Hi **{{ $appointment->client_name }}**,

This is a friendly reminder that you have an upcoming appointment scheduled with us for **{{ $appointment->title }}**. 

<x-mail::panel>
**📅 Date:** {{ $appointment->appointment_date->format('l, F j, Y') }}  
**🕒 Time:** {{ date('h:i A', strtotime($appointment->appointment_time)) }}  
**📍 Location:** {{ $appointment->location }}  
</x-mail::panel>

@if($appointment->notes)
**Meeting Notes / Agenda:**  
{{ $appointment->notes }}
@endif

If you need to reschedule or cancel, please let us know as soon as possible.

<x-mail::button :url="url('/')" color="primary">
Contact Us
</x-mail::button>

We look forward to speaking with you!

Best regards,<br>
**{{ config('app.name') }}**
</x-mail::message>

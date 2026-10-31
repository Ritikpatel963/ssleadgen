<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CalendarNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);

        $appointments = Appointment::whereYear('appointment_date', $year)
            ->whereMonth('appointment_date', $month)
            ->orderBy('appointment_time')
            ->get();

        // Group by day number
        $appointmentsByDay = $appointments->groupBy(fn ($a) => $a->appointment_date->day);

        // Fetch Tasks for this month
        $tasks = Task::whereYear('due_date', $year)
            ->whereMonth('due_date', $month)
            ->orderBy('due_time')
            ->get();

        // Group tasks by day number
        $tasksByDay = $tasks->groupBy(fn ($t) => $t->due_date->day);

        // Today's appointments for sidebar
        $todayAppointments = Appointment::whereDate('appointment_date', today())
            ->orderBy('appointment_time')
            ->get();

        $clients = Client::validNames()
            ->whereNotNull('first_name')
            ->where('first_name', '!=', '')
            ->select('id', 'first_name', 'last_name', 'email', 'phone')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $todayTasks = Task::whereDate('due_date', today())
            ->orderBy('due_time')
            ->get();

        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $weekEnd   = $weekStart->copy()->addDays(6);
        $weekTasks = Task::whereBetween('due_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('due_time')
            ->get()
            ->groupBy(fn ($t) => $t->due_date->toDateString());

        return view('pages.utilities.calendar', compact('appointmentsByDay', 'tasksByDay', 'todayAppointments', 'year', 'month', 'clients', 'todayTasks', 'weekTasks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'appointment_date' => 'required|date',
            'appointment_time' => 'required',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        $client = Client::findOrFail($request->client_id);
        $clientName = trim($client->first_name.' '.$client->last_name);

        $appointment = Appointment::create([
            'title' => $request->title,
            'client_id' => $request->client_id,
            'client_name' => $clientName,
            'appointment_date' => $request->appointment_date,
            'appointment_time' => $request->appointment_time,
            'location' => $request->location ?? 'Zoom Video Call',
            'notes' => $request->notes,
            'status' => 'Scheduled',
            'color' => $request->color ?? 'event-blue',
        ]);

        // Notify all super-admins of the new appointment
        $superAdmins = User::whereHas('role', fn ($q) => $q->where('is_super_admin', true))->get();
        Notification::send($superAdmins, new CalendarNotification(
            $appointment->title,
            $appointment->client_name,
            $appointment->appointment_date->format('d M Y'),
            $appointment->appointment_time
        ));

        // Redirect back to the month of the new appointment
        $date = Carbon::parse($request->appointment_date);

        return redirect()->route('calendar.index', ['year' => $date->year, 'month' => $date->month])
            ->with('success', 'Appointment scheduled successfully.');
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return back()->with('success', 'Appointment deleted.');
    }

    public function sendReminder(Appointment $appointment)
    {
        if (! $appointment->client || ! $appointment->client->email) {
            return response()->json(['success' => false, 'message' => 'Client email not found.']);
        }

        try {
            Mail::to($appointment->client->email)
                ->send(new AppointmentReminder($appointment));

            return response()->json(['success' => true, 'message' => 'Reminder sent successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send reminder: '.$e->getMessage()]);
        }
    }
}

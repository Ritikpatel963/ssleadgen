@extends('layouts.app')
@section('title', 'Communications Hub')
@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Client Communications & Message Logs</h4>
                    <p class="text-muted fs-13 mb-0">Log emails, SMS alerts, and phone call notes sent to clients.</p>
                </div>
                <button class="btn btn-primary btn-sm px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#sendMsgModal"><i class="feather-send me-1"></i> Send Message</button>
            </div>

            <div class="card-widget">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="fs-12 text-muted text-uppercase fw-semibold" style="background: #F8FAFC;">
                                <th>Recipient</th>
                                <th>Channel</th>
                                <th>Subject / Summary</th>
                                <th>Timestamp</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold text-dark fs-13">Kishore Kumar</td>
                                <td><span class="badge bg-soft-primary text-primary">Email</span></td>
                                <td class="fs-13 text-muted">Annual Policy Review Reminder & Renewal Notice</td>
                                <td class="fs-13 text-muted">18 Aug 2026, 10:45 AM</td>
                                <td><span class="badge bg-soft-success text-success">Delivered</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">Rahul Sharma</td>
                                <td><span class="badge bg-soft-info text-info">SMS Alert</span></td>
                                <td class="fs-13 text-muted">Your AIA Policy Schedule is ready for signature.</td>
                                <td class="fs-13 text-muted">18 Aug 2026, 09:12 AM</td>
                                <td><span class="badge bg-soft-success text-success">Delivered</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            

<!-- Modal: Send Message -->
    <div class="modal fade" id="sendMsgModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: #0A192F;">
                    <h5 class="modal-title text-white mb-0"><i class="feather-send me-2"></i> Send Client Communication</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form onsubmit="event.preventDefault(); alert('Message Sent Successfully!'); $('#sendMsgModal').modal('hide');">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">Recipient Client <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" placeholder="Select Client" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">Communication Channel</label>
                            <select class="form-select">
                                <option value="Email">Email</option>
                                <option value="SMS Alert">SMS Alert</option>
                                <option value="Phone Call Note">Phone Call Note</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">Subject / Heading</label>
                            <input type="text" class="form-control" placeholder="Subject">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">Message Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" rows="3" placeholder="Type message body..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold">Send Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@push('scripts')
    <script src="{{ asset('assets/js/dashboard-redesign.js') }}"></script>
@endpush

@endsection
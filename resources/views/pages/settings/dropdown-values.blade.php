<div id="dropdownManagerContent">
    <div class="card-widget">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <h6 class="widget-title mb-0">
                Category Master
                <span class="badge bg-soft-primary text-primary ms-1 fs-11">{{ $items->count() }}</span>
            </h6>
            <button type="button" class="btn btn-primary btn-sm px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#dropdownValueModal">
                <i class="feather-plus me-1"></i> Add Category
            </button>
        </div>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <label for="dropdownGroupFilter" class="form-label fw-semibold fs-13 mb-0">Category Type</label>
            <select id="dropdownGroupFilter" class="form-select" style="max-width: 320px;">
                <option value="" disabled {{ empty($group) ? 'selected' : '' }}>-- Select Category Type --</option>
                @foreach($groups as $groupKey)
                    <option value="{{ route('settings.dropdowns', ['group' => $groupKey]) }}" data-group="{{ $groupKey }}" {{ $group === $groupKey ? 'selected' : '' }}>
                        {{ ucwords(str_replace('_', ' ', $groupKey)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="dropdownValuesTable">
                <thead>
                    <tr class="fs-12 text-muted text-uppercase fw-semibold">
                        <th>Sort Order</th>
                        <th>Value</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td class="fs-13 text-muted" data-order="{{ $group === 'lead_category' && isset($item->display_order) ? $item->display_order : $item->sort_order }}">{{ $group === 'lead_category' && isset($item->display_order) ? $item->display_order : $item->sort_order }}</td>
                            <td class="fw-bold text-dark fs-13">{{ $item->name }}</td>
                            <td>
                                @if($item->is_active)
                                    <span class="status-pill-inforce">Active</span>
                                @else
                                    <span class="status-pill-inactive">Inactive</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="action-kebab-wrapper">
                                    <button type="button" class="action-kebab-btn" aria-label="Category actions"><i class="feather-more-vertical"></i></button>
                                    <div class="action-kebab-dropdown">
                                        <a href="javascript:void(0);" class="action-kebab-item"
                                            data-bs-toggle="modal" data-bs-target="#dropdownValueModal"
                                            data-id="{{ $item->id }}" data-group="{{ $item->group }}"
                                            data-name="{{ $item->name }}" data-status="{{ $item->is_active ? '1' : '0' }}">
                                            <i class="feather-edit text-primary me-1"></i> Edit Category
                                        </a>
                                        <form action="{{ route('settings.dropdowns.destroy', $item) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-kebab-item text-danger w-100 text-start border-0 bg-transparent" onclick="confirmFormSubmit(event, 'Delete this category?', this.closest('form'))">
                                                <i class="feather-trash-2 text-danger me-1"></i> Delete Category
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="dropdownValueModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                <h5 class="modal-title text-white mb-0" id="dropdownValueModalTitle"><i class="feather-plus me-2"></i> Add Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="dropdownValueForm" method="POST">
                @csrf
                <input type="hidden" name="_method" id="dropdownValueMethod" value="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold fs-13 text-dark">Category Type</label>
        <select class="form-select" name="group" id="dropdownGroupSelect" required>
                            <option value="" disabled {{ empty($group) ? 'selected' : '' }}>-- Select Category Type --</option>
                            @foreach($groups as $groupKey)
                                 <option value="{{ $groupKey }}" {{ $group === $groupKey ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $groupKey)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold fs-13 text-dark">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="dropdownValueName" placeholder="e.g. Allianz" required>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="dropdownValueStatus" value="1" checked>
                        <label class="form-check-label" for="dropdownValueStatus">Active</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold" id="dropdownValueSubmitBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

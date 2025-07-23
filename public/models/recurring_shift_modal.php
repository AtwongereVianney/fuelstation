<!-- Trigger Button -->
<button class="btn btn-warning btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#recurringShiftModal">
  <i class="bi bi-repeat"></i> Create Recurring Shifts
</button>

<!-- Recurring Shift Modal -->
<div class="modal fade" id="recurringShiftModal" tabindex="-1" aria-labelledby="recurringShiftModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="recurringShiftModalLabel">Create Recurring Shifts</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Shift</label>
          <select class="form-select" name="recurring_shift_id" required>
            <?php foreach ($shifts as $s): ?>
              <option value="<?php echo $s['id']; ?>"><?php echo h($s['shift_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Start Date</label>
          <input type="date" class="form-control" name="recurring_start_date" required>
        </div>
        <div class="mb-2">
          <label class="form-label">End Date</label>
          <input type="date" class="form-control" name="recurring_end_date" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Weekday Attendants</label>
          <select class="form-select" name="weekday_user_ids[]" multiple required>
            <?php foreach ($users as $u): ?>
              <option value="<?php echo $u['id']; ?>"><?php echo h($u['first_name'] . ' ' . $u['last_name']); ?></option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple.</small>
        </div>
        <div class="mb-2">
          <label class="form-label">Weekend Attendants</label>
          <select class="form-select" name="weekend_user_ids[]" multiple required>
            <?php foreach ($users as $u): ?>
              <option value="<?php echo $u['id']; ?>"><?php echo h($u['first_name'] . ' ' . $u['last_name']); ?></option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning" name="create_recurring_shifts">Create</button>
      </div>
    </form>
  </div>
</div> 
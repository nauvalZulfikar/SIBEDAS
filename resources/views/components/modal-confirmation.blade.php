@props(['buttonText' => 'Confirm', 'confirmationMessage' => 'Are you sure?'])

<div class="modal fade" id="modalConfirmation" tabindex="-1" aria-labelledby="modalConfirmationTitle">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <p class="confirmation-message">{{$confirmationMessage}}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                    data-bs-dismiss="modal" id="btnCloseModal">Close</button>
                <button type="button" class="btn btn-primary" id="btnSaveConfirmation">{{$buttonText}}</button>
            </div>
        </div>
    </div>
</div>
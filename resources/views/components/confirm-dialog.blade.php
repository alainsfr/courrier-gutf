<dialog class="ui-confirm-dialog" data-ui-confirm-dialog aria-labelledby="ui-confirm-title" aria-describedby="ui-confirm-message">
    <h2 id="ui-confirm-title">{{ __('ui.confirm_title') }}</h2>
    <p id="ui-confirm-message" data-ui-confirm-message></p>
    <div class="ui-confirm-actions">
        <x-buttons.secondary data-ui-confirm-cancel>{{ __('ui.cancel') }}</x-buttons.secondary>
        <x-buttons.danger type="button" data-ui-confirm-accept>{{ __('ui.confirm') }}</x-buttons.danger>
    </div>
</dialog>

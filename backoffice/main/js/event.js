function LoadEventCenterMenu() {
    const urlParams = new URLSearchParams(window.location.search);
    const event_id = urlParams.get('key') || urlParams.get('id') || '';
    const current_page = (window.location.pathname.split('/').pop() || '').replace('.php', '').toLowerCase();

    $.ajax({
        beforeSend: function() {
            ShowLoadingOverlay("#DetailSection");
        },
        type: "POST",
        url: "core.php",
        data: {
            request_state: "list_event_detail",
            request_function: "get_menu_event",
            event_id: event_id,
            current_page: current_page
        },
        dataType: "json",
        success: function (response) {
            if(response.result == 1){
                RenderEventCenterMenu(response.data);
            } else {
                Swal.fire({
                    title: "แจ้งเตือน",
                    html: '<span class="fw-bold text-danger">'+response.msg+'</span>',
                    icon: "error",
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    timer: 2000,
                    timerProgressBar: true,
                });
            }
        },
        complete: function(response) {
            HideLoadingOverlay("#DetailSection");
        },
        error: function(jqXHR, exception) {
            ShowErrorAjax(jqXHR, exception);
        }
    });
}
function RenderEventCenterMenu(data) {
    const menu_items = data.menu_items || [];
    const access_level = data.access_level || '';
    const event_id = data.event_id || '';
    const current_page = data.current_page || '';


    const linksHtml = menu_items.map(function(item) {
        const targetUrl = event_id ? (item.url + '?key=' + encodeURIComponent(event_id)) : item.url;
        const isActive = item.key === current_page;
        const buttonClass = isActive ? 'btn btn-primary' : 'btn btn-outline-primary';

        return '<a href="' + targetUrl + '" class="' + buttonClass + ' d-inline-flex align-items-center gap-2 px-3 py-2">'
            + '<i class="' + item.icon + '"></i>'
            + '<span>' + item.label + '</span>'
            + '</a>';
    }).join('');

    const menuHtml = '<div class="card bg-white border-0 rounded-3 mb-0">'
        + '<div class="card-body p-3">'
        + '<div class="d-flex flex-wrap align-items-center gap-2">'
        + linksHtml
        + '</div>'
        + '</div>'
        + '</div>';
    $('#EventCenterMenu').html(menuHtml);
} 
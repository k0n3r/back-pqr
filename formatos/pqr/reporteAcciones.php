<script>
    $(function() {
        let params = $('#script_grid').data('params');
        $('#script_grid').removeAttr('data-params');

        $(document).on('click', '.addTask', function() {

            let documentId = $(this).data('id');

            top.topModal({
                url: `views/tareas/crear.php`,
                params: {
                    className: 'Saia\\Pqr\\Controllers\\TaskEvents',
                    documentId: documentId
                },
                size: 'modal-lg',
                title: 'Tarea',
                buttons: {},
                afterHide: function() {
                    $('#table').bootstrapTable("refresh");
                }
            });

        });

        $(document).on('click', '.viewTask', function() {

            let documentId = $(this).data('id');

            let options = {
                url: `views/tareas/lista_documento.php`,
                params: {
                    documentId: documentId
                },
                title: 'Tareas del documento',
                size: 'modal-lg',
                buttons: {
                    cancel: {
                        label: 'Cerrar',
                        class: 'btn btn-danger'
                    }
                }
            };
            top.topModal(options);
        });

        $(document).on('click', '.cancel', function() {

            let iddocumento = $(this).data('id');

            top.confirm({
                id: 'question',
                type: 'warning',
                message: '¿Está seguro de anular la PQR?',
                position: 'center',
                timeout: 0,
                overlay: true,
                overlayClose: true,
                closeOnEscape: true,
                closeOnClick: true,
                buttons: [
                    [
                        '<button><b>SI</b></button>',
                        function(instance, toast) {
                            instance.hide({
                                    transitionOut: 'fadeOut'
                                },
                                toast,
                                'button'
                            );

                            $.ajax({
                                type: 'POST',
                                url: `${params.baseUrl}app/documento/anular.php`,
                                data: {
                                    key: localStorage.getItem('key'),
                                    token: localStorage.getItem('token'),
                                    documentId: iddocumento,
                                    observation: 'Se anula la PQR'
                                },
                                dataType: 'json',
                                success: function(response) {

                                    if (response.success) {
                                        top.notification({
                                            message: response.message,
                                            type: 'success'
                                        });
                                        $('#table').bootstrapTable('refresh');
                                    } else {
                                        top.notification({
                                            message: response.message,
                                            type: 'error'
                                        });
                                    }
                                }
                            });
                        },
                        true
                    ],
                    [
                        '<button>NO</button>',
                        function(instance, toast) {
                            instance.hide({
                                    transitionOut: 'fadeOut'
                                },
                                toast,
                                'button'
                            );
                        },
                        true
                    ]
                ]
            });

        });

        $(document).on('click', '.answer', function() {

            let documentId = $(this).data('id');

            top.topModal({
                url: `app/modules/back_pqr/views/responder.php`,
                params: {
                    documentId: documentId
                },
                size: 'modal-lg',
                title: 'Responder',
                buttons: {
                    success: {
                        label: 'Guardar',
                        class: 'btn btn-complete'
                    },
                    cancel: {
                        label: 'Cancelar',
                        class: 'btn btn-danger'
                    }
                },
                afterHide: function() {
                    $('#table').bootstrapTable("refresh");
                }
            });

        });

    });
</script>
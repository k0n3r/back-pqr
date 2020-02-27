<script>
    $(function() {
        let params = $('#script_grid').data('params');
        $('#script_grid').removeAttr('data-params');

        $(document).on('click', '.addResponsable', function() {

            let iddocumento = $(this).data('id');

            top.topModal({
                url: `views/tareas/crear.php`,
                params: {
                    className: 'Saia\\Pqr\\Controllers\\TaskEvents',
                    documentId: iddocumento
                },
                size: 'modal-lg',
                title: 'Tarea',
                buttons: {
                    success: {
                        label: "Guardar",
                        class: "btn btn-complete"
                    }
                },
                onSuccess: function() {
                    $('#table').bootstrapTable("refresh");
                }
            });

        });

        $(document).on('click', '.cancel', function() {

            let iddocumento = $(this).data('id');

            top.confirm({
                id: 'question',
                type: 'warning',
                message: '¿Está seguro de Anular la PQR?',
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

                            // $.ajax({
                            //     type: 'POST',
                            //     url: `${params.baseUrl}app/trd/serie_version/actualizar_estado.php`,
                            //     data: {
                            //         key: localStorage.getItem('key'),
                            //         token: localStorage.getItem('token'),
                            //         idversion: idversion
                            //     },
                            //     dataType: 'json',
                            //     success: function(response) {

                            //         if (response.success) {
                            //             top.notification({
                            //                 message: 'Datos actualizados!',
                            //                 type: 'success'
                            //             });
                            //             $('#table').bootstrapTable('refresh');
                            //         } else {
                            //             top.notification({
                            //                 message: response.message,
                            //                 type: 'error'
                            //             });
                            //         }
                            //     }
                            // });
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

    });
</script>
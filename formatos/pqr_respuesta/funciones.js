//evento ejecutado en el adicionar
function add(data) {
  console.log(data);
  $.post(
    `${data.baseUrl}app/modules/back_pqr/app/request.php`,
    {
      class: 'FtPqrRespuestaController',
      method: 'loadField',
      key: localStorage.getItem('key'),
      token: localStorage.getItem('token'),
      data: {
        iddocPadre: data.padre
      }
    },
    function (response) {
      if (response.success) {
        let data = response.data;

        if (typeof data.destino === 'object') {
          $("#destino").select2('close');
          let option = new Option(data.destino.text, data.destino.id, true, true);
          $("#destino").append(option).trigger('change');
        }

        if (data.tipo_distribucion) {
          $("#tipo_distribucion").val(data.tipo_distribucion)
            .trigger('change');
        }

        if (data.despedida) {
          $("#despedida").val(data.despedida)
            .trigger('change');
        }

        if (data.asunto) {
          $("#asunto").val(data.asunto);
        }

      } else {
        console.error(response)
      }
    },
    'json'
  );

  addEdit(data);
}

function edit(data) {
  if (data.numero) {
    top.notification({
      type: 'error',
      message: 'El documento ya se encuentra radicado, NO se puede editar'
    });
    window.history.back();
  }
  addEdit(data);
}

function addEdit(data) {

  $('#ciudad_origen').select2({
    minimumInputLength: 2,
    language: 'es',
    ajax: {
      type: 'POST',
      dataType: 'json',
      url: `${data.baseUrl}app/configuracion/autocompletar_municipios.php`,
      data: function (params) {
        return {
          term: params.term,
          key: localStorage.getItem('key'),
          token: localStorage.getItem('token')
        };
      },
      processResults: function (response) {
        return { results: response.data }
      }
    }
  });

  $("#group_otra_despedida").hide();
  if ($("#otra_despedida").val() != "") {
    $("#group_otra_despedida").show();
  }

  $("#despedida").on('select2:select', function (e) {
    let key = e.params.data.element.dataset.key;
    if (+key == 3) {
      $("#otra_despedida").addClass("required");
      $("#group_otra_despedida").show();
    } else {
      $("#otra_despedida").removeClass("required");
      $("#otra_despedida").val('');
      $("#group_otra_despedida").hide();
    }
  });

  $("#tipo_distribucion").on('select2:select', function (e) {
    let key = e.params.data.element.dataset.key;
    if (+key == 4) {//Enviar por email
      $("#group_sol_encuesta").show();
      $("#sol_encuesta").val(1);
    } else {
      $("#sol_encuesta").val(0);
      $("#group_sol_encuesta").hide();
    }
  });

}

//evento ejecutado en el mostrar
function show(data) {
  let baseUrl = window.getBaseUrl();
}

//evento ejecutado anterior al adicionar
function beforeSendAdd() {
  return new Promise((resolve, reject) => {
    resolve();
  });
}

//evento ejecutado posterior al adicionar
function afterSendAdd(xhr) {
  return new Promise((resolve, reject) => {
    resolve();
  });
}

//evento ejecutado anterior al editar
function beforeSendEdit() {
  return new Promise((resolve, reject) => {
    resolve();
  });
}

//evento ejecutado posterior al editar
function afterSendEdit(xhr) {
  return new Promise((resolve, reject) => {
    resolve();
  });
}

//evento ejecutado anterior al devolver o rechazar
function beforeReject() {
  return new Promise((resolve, reject) => {
    resolve();
  });
}

//evento ejecutado posterior al devolver o rechazar
function afterReject(xhr) {
  return new Promise((resolve, reject) => {
    resolve();
  });
}

//evento ejecutado anterior al confirmar o aprobar
function beforeConfirm() {
  return new Promise((resolve, reject) => {
    resolve();
  });
}

//evento ejecutado posterior al confirmar o aprobar
function afterConfirm(xhr) {
  return new Promise((resolve, reject) => {
    resolve();
  });
}
//evento ejecutado en el adicionar
function add(data) {
  $("#group_otra_despedida").hide();
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
      $("#group_otra_despedida").show();
    } else {
      $("#otra_despedida").val('');
      $("#group_otra_despedida").hide();
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
//evento ejecutado en el adicionar
function add(data) {
  addEdit(data);
}

//evento ejecutado en el editar
function edit(data) {
  top.notification({
    type: 'error',
    message: 'El documento ya se encuentra radicado, NO se puede editar'
  });
  window.history.back();

  addEdit(data);
}

function addEdit(data) {
  var baseUrl = localStorage.getItem("baseUrl");

  if (!data.sys_subtipo) {
    $("#group_sys_subtipo").remove();
  }

  $(".pqrAutocomplete").each(function (index, element) {
    $("#" + element.id).select2({
      language: "es",
      placeholder: "Ingrese el nombre",
      multiple: false,
      ajax: {
        delay: 400,
        url: `${baseUrl}app/modules/back_pqr/app/request.php`,
        dataType: "json",
        data: function (p) {
          var query = {
            key: localStorage.getItem("key"),
            token: localStorage.getItem("token"),
            class: "RequestProcessorController",
            method: "getListForField",
            data: {
              name: element.id,
              term: p.term
            }
          };
          return query;
        }
      }
    });

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
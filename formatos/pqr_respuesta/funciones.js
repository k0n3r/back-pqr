//evento ejecutado en el adicionar
function add(data) {
  if (Array.isArray(data.fk_pqr)) {
    console.log(data)

  } else {
    viewEmail(+data.fk_pqr)
  }

  $("#fk_response_template").change(function () {

  });


  function viewEmail(id) {
    $.ajax({
      type: "POST",
      url: `${data.baseUrl}app/modules/back_pqr/app/request.php`,
      data: {
        key: localStorage.getItem("key"),
        token: localStorage.getItem("token"),
        class: "FtPqrController",
        method: "getEmail",
        params: {
          id: id
        }
      },
      dataType: "json",
      success(response) {
        if (response.success) {
          $("#email").val(response.data);
        } else {
          top.notification({
            message: response.message,
            type: "error"
          });
        }
      },
      error(error) {
        console.error(error);
      }
    });
  }
}
//evento ejecutado en el editar
function edit(data) {

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
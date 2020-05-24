<?php

namespace Saia\Pqr\controllers;

use Saia\models\formatos\Formato;
use Saia\Pqr\webserviceGenerator\fieldGenerator\HiddenCustom;
use Saia\Pqr\webserviceGenerator\WebserviceGenerator;

class WebserviceCalificacion extends WebserviceGenerator
{

  const DIRECTORY = '../' . SettingController::DIRECTORY_CLASIFICACION;

  protected $Formato;

  public function __construct(Formato $Formato)
  {
    $this->Formato = $Formato;
    $this->setDirectory(self::DIRECTORY);
  }

  /**
   * Obtiene el ID del formato
   *
   * @return int
   * @author Andres Agudelo <andres.agudelo@cerok.com>
   * @date 2020
   */
  protected function getFormatId(): int
  {
    return (int) $this->Formato->getPK();
  }

  /**
   * Obtiene los campos del formulario que estaran en el adicionar del webservice
   *
   * @return array
   * @author Andres Agudelo <andres.agudelo@cerok.com>
   * @date 2020
   */
  protected function getFormatFields(): array
  {
    $data = [];
    $records = $this->Formato->getFields();
    foreach ($records as $CamposFormato) {
      if (!$CamposFormato->isSystemField()) {
        $data[] = [
          'type' => 'camposFormato',
          'instance' => $CamposFormato
        ];
      }
    }

    $data[] = [
      'type' => 'custom',
      'instance' => new HiddenCustom([
        'nombre' => 'anterior',
        'value' => '0',
      ])
    ];

    $data[] = [
      'type' => 'custom',
      'instance' => new HiddenCustom([
        'nombre' => 'ft_pqr_respuesta',
        'value' => '0',
      ])
    ];
    return $data;
  }

  /**
   * Obtiene el nombre del formulario
   *
   * @return string
   * @author Andres Agudelo <andres.agudelo@cerok.com>
   * @date 2020
   */
  protected function getNameForm(): string
  {
    return $this->Formato->etiqueta;
  }

  /**
   * Obtiene el contenido html de los campos que estaran en el adicionar
   * del webservice
   *
   * @return string
   * @author Andres Agudelo <andres.agudelo@cerok.com>
   * @date 2020
   */
  protected function getContent(): string
  {
    $code = parent::getContentDefault();

    return $code;
  }

  /**
   * Crea el contenido js que sera cargado en el adicionar del webservice
   *
   * @return string
   * @author Andres Agudelo <andres.agudelo@cerok.com>
   * @date 2020
   */
  protected function createJsContent(): string
  {
    $url = 'app/modules/back_pqr/app/generateCalificacion.php';
    $contentAditional = $this->getJsAditionalContent();
    $baseUrl = ABSOLUTE_SAIA_ROUTE;
    $formatId = $this->getFormatId();

    $code = <<<JAVASCRIPT

$(function () {
  var baseUrl = '{$baseUrl}';
  var d = getVariableFromUrl('d');
  if (!d) {
    msgError(400);
    return;
  }

  $.ajax({
    type: 'POST',
    dataType: 'json',
    async: false,
    url: `${baseUrl}app/modules/back_pqr/app/decrypt.php`,
    data: {
      dataCrypt: d
    },
    success: function (response) {
      if (response.success) {
        let data = response.data;
        $("#ft_pqr_respuesta").val(data.ft_pqr_respuesta);
        $("#anterior").val(data.anterior);
      } else {
        if(response.code==200){
          msgError(200,response.message);
          return;
        }else{
          msgError(204);
          return;
        }
       
      }
    }, error: function () {
      msgError(500);
      return;
    }
  });

  function getVariableFromUrl(variable) {
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i = 0; i < vars.length; i++) {
      var pair = vars[i].split("=");
      if (pair[0] == variable) {
        return pair[1];
      }
    }
    return false;
  }

  function msgError(code,message='') {
    if(!message){
      message="Por favor ingrese nuevamente desde el link enviado al correo. Code:" + code;
    }
    notification({
      color: 'red',
      message: message
    });
    alert("Pendiente por definir a donde redireccionar");
    return;
  }

  {$contentAditional}

  $("#save_document").click(function () {
    $("#form_buttons").find('button,#spiner').toggleClass('d-none');
    $("#formulario").trigger('submit');
  });

  $("#formulario").validate({
    errorPlacement: function (error, element) {
      let node = element[0];

      if (
        node.tagName == "SELECT" &&
        node.className.indexOf("select2") !== false
      ) {
        error.addClass("pl-2");
        element.next().append(error);
      } else {
        error.insertAfter(element);
      }
    },
    submitHandler: function (form) {
      let data = $('#formulario').serialize();
      $.post(
        `${baseUrl}{$url}`,
        data,
        function (response) {
          if (response.success) {
            notification({
              color: 'green',
              position: "topCenter",
              timeout: 30000,
              message: 'Gracias por brindarnos la oportunidad de conocer su opinión a través de sus respuestas. Con ellas, usted contribuye al crecimiento y mejoramiento de nuestros servicios'
            });
            alert("pendiente definir a donde redireccionar");
          } else {
            console.error(response.message);
            notification({
              color: 'red',
              message: +response.code == 200 ? response.message : 'No fue posible registrar su calificación'
            });
          }
        },
        'json'
      )
        .fail(function () {
          console.error(...arguments)
        })
        .always(function () {
          toggleButton();
        });

      return false;
    },
    invalidHandler: function () {
      toggleButton();
    }
  });

  function toggleButton() {
    $("#form_buttons").find('button,#spiner').toggleClass('d-none');
  }

  function notification(options) {
    let iziDefaultOptions = {
      position: "topRight",
      timeout: 15000,
    };
    options = $.extend({}, iziDefaultOptions, options);
    iziToast.show(options);
  }

});
JAVASCRIPT;
    return $code;
  }
}

<?php

namespace Saia\Pqr\controllers\customFields;

use Saia\Pqr\models\PqrFormField;
use Saia\controllers\generator\webservice\IWsFields;
use Saia\controllers\generator\webservice\fields\Field;

class Autocomplete extends Field implements IWsFields
{

  public function __construct(PqrFormField $PqrFormField)
  {
    parent::__construct($PqrFormField->CamposFormato);
  }

  public function aditionalFiles(): array
  {
    return [
      [
        'origin' => 'views/assets/node_modules/select2/dist/js/select2.min.js',
        'newName' => 'select2.min.js'
      ],
      [
        'origin' => 'views/assets/node_modules/select2/dist/js/i18n/es.js',
        'newName' => 'es.js',
        'subFolder' => 'i18n/'
      ],
      [
        'origin' => 'views/assets/node_modules/select2/dist/css/select2.min.css',
        'newName' => 'select2.min.css'
      ]
    ];
  }

  public function htmlContent(): string
  {
    $requiredClass = $this->getRequiredClass();
    $title = $this->CamposFormato->ayuda ? " title='{$this->CamposFormato->ayuda}'" : '';

    $code = "<div class='form-group form-group-default form-group-default-select2 {$requiredClass}' id='group_{$this->CamposFormato->nombre}'>
            <label{$title}>{$this->getLabel()}</label>
            <select class='full-width {$requiredClass}' name='{$this->CamposFormato->nombre}' id='{$this->CamposFormato->nombre}'>
            <option value=''>Por favor seleccione...</option>
            </select>
        </div>";

    return $code;
  }

  public function jsContent(): ?string
  {
    return <<<JAVASCRIPT
        let options_{$this->CamposFormato->nombre} = {
          language: "es",
          placeholder: "Ingrese el nombre",
          multiple: false,
          ajax: {
            delay: 400,
            url: baseUrl+`app/modules/back_pqr/app/request.php`,
            dataType: "json",
            data: function(p) {
              var query = {
                key: localStorage.getItem("key"),
                token: localStorage.getItem("token"),
                class: "RequestProcessorController",
                method: "getListForField",
                data: {
                  name: '{$this->CamposFormato->nombre}',
                  term: p.term
                }
              };
              return query;
            }
          }
        };
        $('#{$this->CamposFormato->nombre}').select2(options_{$this->CamposFormato->nombre});
JAVASCRIPT;
  }
}

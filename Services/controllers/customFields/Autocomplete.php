<?php

namespace App\Bundles\pqr\Services\controllers\customFields;

use App\Bundles\pqr\Services\models\PqrFormField;
use Saia\controllers\generator\webservice\IWsFields;
use Saia\models\formatos\CamposFormato;

class Autocomplete implements IWsFields
{
    protected CamposFormato $CamposFormato;

    public function __construct(PqrFormField $PqrFormField)
    {
        $this->CamposFormato = $PqrFormField->getCamposFormato();
    }

    public function getLibrariesWs(): array
    {
        return [
            '/views/node_modules/select2/dist/js/select2.min.js',
            '/views/assets/theme/assets/js/cerok_libraries/ui/globalSelect2.js',
            '/views/node_modules/select2/dist/css/select2.min.css',
        ];
    }

    public function getAdditionHTMLWs(): string
    {
        $ComponentBuilder = $this->CamposFormato->getComponentBuilder();

        $requiredClass = $ComponentBuilder->getRequiredClass();
        $label = $ComponentBuilder->getLabel();

        return "<div class='form-group form-group-default form-group-default-select2 $requiredClass' id='group_{$this->CamposFormato->nombre}'>
              <label>$label</label>
              <select class='full-width $requiredClass' name='{$this->CamposFormato->nombre}' id='{$this->CamposFormato->nombre}'>
              <option value=''>Por favor seleccione...</option>
              </select>
          </div>";
    }

    public function getAdditionJsWs(): string
    {
        return <<<JAVASCRIPT
            let options_{$this->CamposFormato->nombre} = {
              minimumInputLength: 3,
              placeholder: "Ingrese el nombre",
              multiple: false,
              ajax: {
                delay: 400,
                url: `/api/pqr/components/autocomplete/list`,
                dataType: "json",
                data: function(p) {
                  return {
                    name: '{$this->CamposFormato->nombre}',
                    data: {
                      term: p.term
                    }
                  };
                }
              }
            };
            $('#{$this->CamposFormato->nombre}').select2(options_{$this->CamposFormato->nombre});
            JAVASCRIPT;
    }

    /**
     * @inheritDoc
     */
    public function getEditionHTMLWs(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getEditionJsWs(): string
    {
        return '';
    }
}

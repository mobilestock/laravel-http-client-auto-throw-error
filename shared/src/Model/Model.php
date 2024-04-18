<?php

namespace MobileStock\Shared\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * @method static static create(array $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Collection<static> fromQuery($query, $bindings = [])
 * @method static \Illuminate\Database\Eloquent\Collection<static> hydrate(array $items)
 */
class Model extends EloquentModel
{
    /**
     * Perform a model update operation.
     * Obs.: Parte do código foi copiado do laravel
     *
     * @param  Builder  $query
     * @return bool
     */
    protected function performUpdate(Builder $query): bool
    {
        // If the updating event returns false, we will cancel the update operation so
        // developers can hook Validation systems into their models and cancel this
        // operation if the model does not pass validation. Otherwise, we update.
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        // First we need to create a fresh query instance and touch the creation and
        // update timestamp on the model which are maintained by us for developer
        // convenience. Then we will just continue saving the model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        // Once we have run the update operation, we will fire the "updated" event for
        // this model instance. This will allow developers to hook into these after
        // models are updated, giving them a chance to do any special processing.
        $dirty = $this->getDirty();

        if (count($dirty) > 0) {
            $linhasAfetadas = $this->setKeysForSaveQuery($query)->update($dirty);

            if ($linhasAfetadas !== 1) {
                throw new RowCountFailVerificationException(
                    "Erro ao atualizar registro. Número de registros afetados: $linhasAfetadas"
                );
            }

            $this->syncChanges();

            $this->fireModelEvent('updated', false);
        }

        return true;
    }

    /**
     * Perform the actual delete query on this model instance.
     * Obs.: Parte do código foi copiado do laravel
     *
     * @return void
     */
    protected function performDeleteOnModel(): void
    {
        $linhasAfetadas = $this->setKeysForSaveQuery($this->newModelQuery())->delete();

        if ($linhasAfetadas !== 1) {
            throw new RowCountFailVerificationException(
                "Erro ao remover registro. Número de registros afetados: $linhasAfetadas"
            );
        }

        $this->exists = false;
    }

    public function newModelQuery(): Builder
    {
        return $this->registerGlobalScopes(parent::newModelQuery());
    }
}

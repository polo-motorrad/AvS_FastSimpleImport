<?php
/**
 * Created by PhpStorm.
 * User: npreuss
 * Date: 9/29/14
 * Time: 1:19 PM
 */

class AvS_FastSimpleImport_Model_Import_Entity_Order extends Mage_ImportExport_Model_Import_Entity_Abstract
{

    var $_orders;

    public function __construct()
    {
        parent::__construct();
        $this->_initOrders();
    }
    public function setIgnoreDuplicates($ignore)
    {
        $this->_ignoreDuplicates = (boolean)$ignore;
    }


        /**
     * Source model setter.
     *
     * @param array $source
     * @return Mage_ImportExport_Model_Import_Entity_Abstract
     */
    public function setArraySource($source)
    {
        $this->_source = $source;
        $this->_dataValidated = false;

        return $this;
    }

    public function getIgnoreDuplicates()
    {
        return $this->_ignoreDuplicates;
    }

        /**
     * Set the error limit when the importer will stop
     *
     * @param $limit
     */
    public function setErrorLimit($limit)
    {
        if ($limit) {
            $this->_errorsLimit = $limit;
        } else {
            $this->_errorsLimit = 100;
        }
    }
    /**
     * Import behavior setter
     *
     * @param string $behavior
     */
    public function setBehavior($behavior)
    {
        $this->_parameters['behavior'] = $behavior;
    }

    protected function _initOrders()
    {
        $collection = Mage::getResourceModel('sales/order_collection');
        foreach ($collection as $order) {
            $this->_orders[$order->getIncrementId()] = array(
                'increment_id' => $order->getIncrementId(),
                //'shipment' => $order->getShipmentsCollection(),
                //'payment' => $order->getPayment()->getData(),

                'status' => $order->getStatus(),
                'state' => $order->getState(),
            );
            foreach ($order->getShipmentsCollection() as $shipment) {
                $this->_orders[$order->getIncrementId()]['shipments'][] = $shipment->getId();
            }
            if ($order->hasInvoices()) {
                foreach ($order->getInvoiceCollection() as $invoice) {
                    $this->_orders[$order->getIncrementId()]['invoices'][] = $invoice->getIncrementId();
                }
            }
        }

        return $this;
    }
    /**
     * Import data rows.
     *
     * @return boolean
     */
    protected function _importData()
    {
        // TODO: Implement _importData() method.
         if (Mage_ImportExport_Model_Import::BEHAVIOR_DELETE == $this->getBehavior()) {
             // @todo take a look if deletion of orders should be possible at all or if it should cancel orders instead
             $this->_deleteOrders();
        } else {
             $this->_saveOrders();
             //$this->_saveShipments();
             //$this->_saveInvoices();
        }
        Mage::dispatchEvent('catalog_order_import_finish_before', array('adapter'=>$this));
        return true;
    }

    /**
     * EAV entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        // TODO: Implement getEntityTypeCode() method.
        return 'order';
    }


    protected function _saveOrders()
    {
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
//            $this->_initWorkBunch($bunch);
            foreach ($bunch as $rowNum => $rowData){
                $isValidRow = $this->validateRow($rowData, $rowNum);
                if (false === $isValidRow) {
                    continue;
                }
                $rowData = $this->_prepareRowForDb($rowData);
                Mage::log($rowData);
            }
        }
    }
    protected function  _prepareRowForDb(array $data)
    {
        $data = parent::_prepareRowForDb($data);

        if (strpos($data['status'], 'STATE_') !== false) {
            $data['status'] = constant('Mage_Sales_Model_Order::'.$data['status']);
        }
        if (strpos($data['action'], 'ACTION_') !== false) {
            $data['action'] = constant('Mage_Sales_Model_Order::'.$data['action']);
        }
        return $data;
    }
    /**
     * Validate data row.
     *
     * @param array $rowData
     * @param int $rowNum
     * @return boolean
     */
    public function validateRow(array $rowData, $rowNum)
    {
        // TODO: Implement validateRow() method.
        if (isset($this->_validatedRows[$rowNum])) {
            return !isset($this->_invalidRows[$rowNum]);
        }
        $this->_validatedRows[$rowNum] = true;
        $this->_processedEntitiesCount++;

        $orderId = $rowData['order_id'];
        //check if order exists
        if (!array_key_exists($orderId, $this->_orders))
        {
            return false;
        }
        return true;
    }

}
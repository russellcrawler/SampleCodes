<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Location extends MY_Controller
{
    const ZOOM_SHOP = 13;
    const ZOOM_SERVICE = 10;

    const ZOOM_DEFAULT = self::ZOOM_SERVICE;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('address_model');
    }

    public function addressLocation(int $addressID)
    {
        /** @var Address_model[] $addresses */
        $addresses = $this->address_model->where('id', $addressID)->getAll();

        if (!empty($addresses)) {
            $shopName = $this->input->get('shop', true);

            $addressesUpdated = $this->displayAddress($addresses, self::ZOOM_SHOP, !empty($shopName) ? urldecode($shopName) . '<br/>' : '');

            if (!empty($addressesUpdated)) {
                $this->address_model->updateBatch($addressesUpdated);
            }
        }
    }

    public function locationByString($address)
    {
        $addressParts = explode(';', urldecode($address));

        $addressObj = new Address_model();

        foreach ($addressParts as $address) {
            $part = explode('-', $address);
            $part[$part[0]] = $part[1];

            if (isset($part['zip'])) {
                $addressObj->setPostalCode($part['zip']);
            }
        }

        $this->displayAddress(array($addressObj), self::ZOOM_SHOP);
    }

    /**
     * @param Address_model[] $addresses
     * @param $zoom
     * @param $additionalString
     * @return array
     */
    private function displayAddress($addresses, $zoom = self::ZOOM_DEFAULT, $additionalString = '')
    {
        $this->load->library('locationService', null, 'location');

        $locations = array();
        $addressesUpdated = array();

        foreach ($addresses as $address) {
            if (empty($address->getLatitude()) || empty($address->getLongitude())) {
                $addressCoordinates = $this->location->getCoordinatesByAddress($address->getAddress());

                if (!empty($addressCoordinates)) {
                    $address->setLatitude($addressCoordinates['lat'])
                        ->setLongitude($addressCoordinates['lon']);

                    $addressesUpdated[] = $address;
                }
            }

            $locations[] = array(
                'coord' => array(
                    'lat' => $address->getLatitude(),
                    'lon' => $address->getLongitude(),
                ),
                'content' => $additionalString .  $address->getAddress(true),
            );
        }

        $this->load->view('site/shop/_modal/_addressModal', array(
            'locations' => $locations,
            'zoom' => $zoom,
        ));

        return $addressesUpdated;
    }
}
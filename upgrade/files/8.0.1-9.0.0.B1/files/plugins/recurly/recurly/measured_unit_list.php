<?php

class Recurly_MeasuredUnitList extends Recurly_Pager
{
  public static function get($params = null, $client = null) {
    $uri = self::_uriWithParams(Recurly_Client::PATH_MEASURED_UNITS, $params);
    return new self($uri, $client);
  }

  protected function getNodeName() {
    return 'measured_units';
  }
}

<?php

/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */
namespace RexFeed\Google\Service\ShoppingContent;

class PosCustomBatchResponse extends \RexFeed\Google\Collection
{
    protected $collection_key = 'entries';
    protected $entriesType = PosCustomBatchResponseEntry::class;
    protected $entriesDataType = 'array';
    /**
     * @var string
     */
    public $kind;
    /**
     * @param PosCustomBatchResponseEntry[]
     */
    public function setEntries($entries)
    {
        $this->entries = $entries;
    }
    /**
     * @return PosCustomBatchResponseEntry[]
     */
    public function getEntries()
    {
        return $this->entries;
    }
    /**
     * @param string
     */
    public function setKind($kind)
    {
        $this->kind = $kind;
    }
    /**
     * @return string
     */
    public function getKind()
    {
        return $this->kind;
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
\class_alias(PosCustomBatchResponse::class, 'RexFeed\\Google_Service_ShoppingContent_PosCustomBatchResponse');

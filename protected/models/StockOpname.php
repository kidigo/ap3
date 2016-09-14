<?php

/**
 * This is the model class for table "stock_opname".
 *
 * The followings are the available columns in table 'stock_opname':
 * @property string $id
 * @property string $tanggal
 * @property string $nomor
 * @property string $rak_id
 * @property string $keterangan
 * @property integer $status
 * @property string $updated_at
 * @property string $updated_by
 * @property string $created_at
 *
 * The followings are the available model relations:
 * @property BarangRak $rak
 * @property User $updatedBy
 * @property StockOpnameDetail[] $stockOpnameDetails
 */
class StockOpname extends CActiveRecord
{

    const STATUS_DRAFT = 0;
    const STATUS_SO = 1;

    public $max; //untuk penomoran surat
    public $namaUpdatedBy;

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'stock_opname';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('status', 'numerical', 'integerOnly' => true),
            array('nomor', 'length', 'max' => 45),
            array('rak_id, updated_by', 'length', 'max' => 10),
            array('keterangan', 'length', 'max' => 500),
            array('tanggal, created_at, updated_at, updated_by', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, tanggal, nomor, rak_id, keterangan, status, updated_at, updated_by, created_at, namaUpdatedBy', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'rak' => array(self::BELONGS_TO, 'RakBarang', 'rak_id'),
            'updatedBy' => array(self::BELONGS_TO, 'User', 'updated_by'),
            'stockOpnameDetails' => array(self::HAS_MANY, 'StockOpnameDetail', 'stock_opname_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'tanggal' => 'Tanggal',
            'nomor' => 'Nomor',
            'rak_id' => 'Rak',
            'keterangan' => 'Keterangan',
            'status' => 'Status',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
            'created_at' => 'Created At',
            'namaUpdatedBy' => 'User'
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     */
    public function search()
    {
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria = new CDbCriteria;

        $criteria->compare('id', $this->id, true);
        $criteria->compare("DATE_FORMAT(t.tanggal, '%d-%m-%Y')", $this->tanggal, true);
        $criteria->compare('nomor', $this->nomor, true);
        $criteria->compare('keterangan', $this->keterangan, true);
        $criteria->compare('status', $this->status);
        $criteria->compare('updated_at', $this->updated_at, true);
        $criteria->compare('updated_by', $this->updated_by, true);
        $criteria->compare('created_at', $this->created_at, true);

        if ($this->rak_id == 'null') {
            $criteria->addCondition('rak_id is null');
        } else {
            $criteria->compare('rak_id', $this->rak_id);
        }

        $criteria->with = ['updatedBy'];
        $criteria->compare('updatedBy.nama_lengkap', $this->namaUpdatedBy, true);

        $sort = [
            'defaultOrder' => 't.status, tanggal desc',
            'attributes' => [
                '*',
                'namaUpdatedBy' => [
                    'asc' => 'updatedBy.nama_lengkap',
                    'desc' => 'updatedBy.nama_lengkap desc'
                ],
            ]
        ];

        return new CActiveDataProvider($this, [
            'criteria' => $criteria,
            'sort' => $sort
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return StockOpname the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function beforeSave()
    {

        if ($this->isNewRecord) {
            $this->created_at = date('Y-m-d H:i:s');
            /*
             * Tanggal akan diupdate jika melalui proses simpanPenjualan
             * bersamaan dengan dapat nomor
             */
            $this->tanggal = date('Y-m-d H:i:s');
        }
        $this->updated_at = date("Y-m-d H:i:s");
        $this->updated_by = Yii::app()->user->id;
        // Jika disimpan melalui proses simpan so
        if ($this->scenario === 'simpanSo') {
            // Status diubah jadi penjualan belum bayar (piutang)
            $this->status = StockOpname::STATUS_SO;
            // Dapat nomor dan tanggal baru
            $this->tanggal = date('Y-m-d H:i:s');
            $this->nomor = $this->generateNomor();
        }
        return parent::beforeSave();
    }

    public function beforeValidate()
    {
        if (empty($this->rak_id)) {
            $this->rak_id = NULL;
        }
        return parent::beforeValidate();
    }

    public function afterFind()
    {
        $this->tanggal = !is_null($this->tanggal) ? date_format(date_create_from_format('Y-m-d H:i:s', $this->tanggal), 'd-m-Y H:i:s') : '0';
        return parent::afterFind();
    }

    public function listStatus()
    {
        return array(
            StockOpname::STATUS_DRAFT => 'Draft',
            StockOpname::STATUS_SO => 'SO'
        );
    }

    public function getNamaStatus()
    {
        $status = $this->listStatus();
        return $status[$this->status];
    }

    public function listRak()
    {
        return CMap::mergeArray(array(
                    'null' => '-'), CHtml::listData(RakBarang::model()->findAll(array(
                                    'select' => 'id, nama',
                                    'order' => 'nama')), 'id', 'nama'));
    }

    public function getNamaRak()
    {
        $rak = $this->listRak();
        if (is_null($this->rak_id)) {
            return '-';
        }
        return $rak[$this->rak_id];
    }

    /**
     * Mencari nomor untuk penomoran surat
     * @return int maksimum+1 atau 1 jika belum ada nomor untuk tahun ini
     */
    public function cariNomor()
    {
        $tahun = date('y');
        $data = $this->find(array(
            'select' => 'max(substring(nomor,9)*1) as max',
            'condition' => "substring(nomor,5,2)='{$tahun}'")
        );

        $value = is_null($data) ? 0 : $data->max;
        return $value + 1;
    }

    /**
     * Membuat nomor surat
     * @return string Nomor sesuai format "[KodeCabang][kodeDokumen][Tahun][Bulan][SequenceNumber]"
     */
    public function generateNomor()
    {
        $config = Config::model()->find("nama='toko.kode'");
        $kodeCabang = $config->nilai;
        $kodeDokumen = KodeDokumen::SO;
        $kodeTahunBulan = date('ym');
        $sequence = substr('0000' . $this->cariNomor(), -5);
        return "{$kodeCabang}{$kodeDokumen}{$kodeTahunBulan}{$sequence}";
    }

    /**
     * Proses simpan Stock Opname.
     * 1. Update status dari draft menjadi so
     * 2. Update inventory terkait dengan cara FIFO
     *    a. Pengurangan, dilakukan seperti penjualan
     *    b. Penambahan, dilakukan pada current inventory, terus ke inv sebelumnya
     * @return boolean True jika sukses
     * @throws Exception
     */
    public function simpanSo()
    {
        $this->scenario = 'simpanSo';
        $transaction = $this->dbConnection->beginTransaction();
        try {
            if ($this->save()) {
                $details = StockOpnameDetail::model()->findAll('stock_opname_id=' . $this->id);
                foreach ($details as $detail) {
                    InventoryBalance::model()->so($this, $detail);
                    if (!is_null($this->rak_id)) {
                        Barang::model()->updateByPk($detail->barang_id, array('rak_id' => $this->rak_id));
                    }
                }
                $transaction->commit();
                return array(
                    'sukses' => true
                );
            } else {
                throw new Exception("Gagal Simpan Stock Opname");
            }
        } catch (Exception $ex) {
            $transaction->rollback();
            return array(
                'sukses' => false,
                'error' => array(
                    'msg' => $ex->getMessage(),
                    'code' => $ex->getCode(),
            ));
        }
    }

}

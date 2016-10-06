<?php
/* @var $this ReportController */
/* @var $model ReporNplsForm */
?>
<div class="row">
    <div class="small-12 columns">

        <ul class="button-group right">
            <li>
                <a href="#" accesskey="p" data-dropdown="print" aria-controls="print" aria-expanded="false" class="tiny bigfont success button dropdown"><i class="fa fa-print fa-fw"></i> <span class="ak">C</span>etak</a>
                <ul id="print" data-dropdown-content class="small f-dropdown content" aria-hidden="true">
                    <?php
                    foreach ($printers as $printer) {
                        ?>
                        <?php
                        if ($printer['tipe_id'] == Device::TIPE_PDF_PRINTER) {
                            /* Jika printer pdf, tambahkan pilihan ukuran kertas */
                            ?>
                            <span class="sub-dropdown"><?= $printer['nama']; ?> <small><?= $printer['keterangan']; ?></small></span>
                            <ul>
                                <?php
                                foreach ($kertasPdf as $key => $value):
                                    ?>
                                    <li><a target="blank" href="<?=
                                        $this->createUrl('printnpls', [
                                            'printId' => $printer['id'],
                                            'kertas' => $key,
                                            'jumlahHari' => $model->jumlahHari,
                                            'profilId' => $model->profilId,
                                            'sisaHariMax' => $model->sisaHariMax,
                                            'sortBy' => $model->sortBy,
                                        ])
                                        ?>"><?= $value; ?></a></li>
                                        <?php
                                    endforeach;
                                    ?>
                            </ul>
                            <?php
                        } else {
                            /* Untuk export CSV
                              ?>
                              <li>
                              <a href="<?=
                              $this->createUrl('printnpls', [
                              'printId' => $printer['id'],
                              'profilId' => $model->profilId,
                              'showDetail' => $model->showDetail,
                              'pilihCetak' => $model->pilihCetak
                              ])
                              ?>">
                              <?= $printer['nama']; ?> <small><?= $printer['keterangan']; ?></small></a>
                              </li>
                              <?php
                             * 
                             */
                        }
                        ?>
                        <?php
                    }
                    ?>
                </ul>
            </li>
        </ul>  
    </div>
</div>
<?php
/*

echo $form->hiddenField($model, 'jumlahHari');
echo $form->hiddenField($model, 'profilId');
echo $form->hiddenField($model, 'sisaHariMax');
echo $form->hiddenField($model, 'sortBy');
?>

<?php

class pupesoft_email_worker {
  public function perform() {
    $boob = mail($this->args['to'], $this->args['subject'], $this->args['content'], $this->args['header'], $this->args['from']);

    if ($boob === FALSE) echo "S�hk�postin l�hetys ep�onnistui: ".$this->args['to']."\n";
    else echo "S�hk�postin l�hetys onnistui: ".$this->args['to']."\n";
  }
}

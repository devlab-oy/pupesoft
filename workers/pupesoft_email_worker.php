<?php

class pupesoft_email_worker {
  public function perform() {
    $boob = mail($this->args['to'], $this->args['subject'], $this->args['content'], $this->args['header'], $this->args['from']);

    if ($boob === FALSE) echo "Sähköpostin lähetys epäonnistui: ".$this->args['to']."\n";
    else echo "Sähköpostin lähetys onnistui: ".$this->args['to']."\n";
  }
}

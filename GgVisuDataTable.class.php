<?php

/**
 * @link http://code.google.com/apis/chart/interactive/docs/reference.html#DataTable
 * Désolé, c'est du boulot de porc cette classe...
 */
class GgVisuDataTable
{
  const TYPE_STRING    = 'string';
  const TYPE_NUMBER    = 'number';
  const TYPE_BOOLEAN   = 'boolean';
  const TYPE_DATE      = 'date';
  const TYPE_DATETIME  = 'datetime';
  const TYPE_TIMEOFDAY = 'timeofday';
  
  private $cols;
  private $rows;
  
  private $row = '';
  
  function __construct ()
  {
    
  }
  
  
  public function AddColumn ($type, $label = null, $id = null)
  {
    $col = '{';
    
    if(!is_null($id))
    {
      $col .= '"id": '.'"'.$id.'", ';
    }
    if(!is_null($label))
    {
      $col .= '"label": '.'"'.$label.'", ';
    }
    
    $col .= '"type": '.'"'.$type.'"';
    
    $col .= '}';
    
    $this->AppendCol($col);
  }
  
  // $finRow : bool, indique la deniere valeur de la ligne
  // $v : valeur de la cellule. si chaine, encadrer de `"`
  // $f : Version de type chaine pour affichage (opt.)
  // $p: propriété de cellule (opt.)
  public function AddRowVals ($finRow, $v, $f = null, $p = null)
  {
    if (!empty($this->row))
    {
      $this->row .= ', ';
    }
    
    $this->row .= '{';
    $this->row .= '"v": '.$v;
    
    if(!is_null($f))
    {
      $this->row .= ', "f": '.'"'.$f.'"';
    }
    if(!is_null($p))
    {
      $this->row .= ', "p": '.$p;
    }
    
    $this->row .= '}';
    
    if ($finRow)
    {
      $this->AppendRow();
    }
  }
  
  private function AppendCol ($col)
  {
    if (is_null($this->cols))
    {
      $this->cols = $col;
    }
    else
    {
      $this->cols .= ', ';
      $this->cols .= $col;
    }
  }
  
  private function AppendRow ()
  {
    $this->row = '{"c": ['.$this->row.']}';
    
    if (is_null($this->rows))
    {
      $this->rows = $this->row;
    }
    else
    {
      $this->rows .= ', ';
      $this->rows .= $this->row;
    }
    
    $this->row = '';
  }
  
  private function GetCols ()
  {
    return
      '"cols": ['
      .$this->cols
      .'], ';
  }
  
  private function GetRows ()
  {
    return
      '"rows": ['
      .$this->rows
      .'], ';
  }
  
  public function Get ()
  {
    return
      '{'
      .$this->GetCols()
      .$this->GetRows()
      .'}';
  }
}
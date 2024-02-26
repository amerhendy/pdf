<?php
namespace Amerhendy\Pdf\Helpers;
use Amerhendy\Pdf\Helpers\TCPDF;
use Amerhendy\Pdf\includes\TCPDF_STATIC;
use Amerhendy\Pdf\includes\TCPDF_SIZES;
use Amerhendy\Pdf\includes\TCPDF_FONTS;
use Amerhendy\Pdf\includes\TCPDF_IMAGES;
use Amerhendy\Pdf\includes\TCPDF_FONT_DATA;
use Amerhendy\Pdf\includes\TCPDF_COLORS;
class AmerPdf extends TCPDF{
	private $customFooterText = "";
	private $customFooterFont =[];
	public function Header() {
        $headerData = $this->getHeaderData();
		$this->setfont($this->customFooterFont[0] ?? 'aealarabiya', $this->customFooterFont[1]??'', $this->customFooterFont[2] ?? 11);
        $this->writeHTML($headerData['string']);
    }
	public function Footer(){
		if(!empty($this->customFooterFont)){
			$this->SetFont($this->customFooterFont[0],$this->customFooterFont[1],$this->customFooterFont[2]);
		}
		$text=str_replace('%pageNumber%',$this->PageNo(),$this->customFooterText);
		//pageNumber
		$this->Cell(
			0, 
			0, 
			$this->writeHTML($text),
			$border=0, 
			$ln=0, 
			$align='C', 
			$fill=false, 
			$link=0, 
			$stretch=0, 
			$ignore_min_height=true, 
			$calign='T', 
			$valign='M');
	}
	public function setFooterHtml($font=array(),$hs, $tc=array(0,0,0), $lc=array(0,0,0),$line=true)
    {
		if($font !== null){
			if(is_array($font)){
				if(!empty($font)){
					if(!isset($font[0])){$font[0]='';}
					if(!isset($font[1])){$font[1]='';}
					if(!isset($font[2])){$font[2]='';}
					$this->customFooterFont=$font;
				}
			}
		}
        $this->customFooterText = $hs;
		$this->setFooterData($tc,$lc);
		$this->footer_margin=100;
		if($line== true){
		$this->SetFooterMargin(100);
			$line_width = (0.85 / $this->k);
			$this->setLineStyle(array('width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $this->footer_line_color));
		}
    }
	   //Separated Header Drawing into it's own function for reuse.
	   public function DrawHeader($header, $w) {
        // Colors, line width and bold font
        // Header
        $this->SetFillColor(233, 136, 64);
        $this->SetTextColor(255);
        $this->SetDrawColor(128, 0, 0);
        $this->SetLineWidth(0.3);
        $this->SetFont('', 'B');        
        $num_headers = count($header);
        for($i = 0; $i < $num_headers; ++$i) {
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
        }
        $this->Ln();
        // Color and font restoration
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('');
    }

    // Colored table
    public function ColoredTable($header,$data) {
        $w = array(10, 40, 20, 20, 20, 20, 20);
        $this->DrawHeader($header, $w);

        // Data
        $fill = 0;
        foreach($data as $row) {
            //Get current number of pages.
            $num_pages = $this->getNumPages();
            $this->startTransaction();
            $this->Cell($w[0], 6, $row[0], 'LR', 0, 'C', $fill);
            $this->Cell($w[1], 6, $row[1], 'LR', 0, 'L', $fill);
            $this->Cell($w[2], 6, $row[2], 'LR', 0, 'C', $fill);
            $this->Cell($w[3], 6, $row[3], 'LR', 0, 'C', $fill);
            $this->Cell($w[4], 6, $row[4], 'LR', 0, 'C', $fill);
            $this->Cell($w[5], 6, $row[5], 'LR', 0, 'C', $fill);
            $this->Cell($w[6], 6, $row[6], 'LR', 0, 'C', $fill);
            $this->Ln();
            //If old number of pages is less than the new number of pages,
            //we hit an automatic page break, and need to rollback.
            if($num_pages < $this->getNumPages())
            {
                //Undo adding the row.
                $this->rollbackTransaction(true);
                //Adds a bottom line onto the current page. 
                //Note: May cause page break itself.
                $this->Cell(array_sum($w), 0, '', 'T');
                //Add a new page.
                $this->AddPage();
                //Draw the header.
                $this->DrawHeader($header, $w);
                //Re-do the row.
                $this->Cell($w[0], 6, $row[0], 'LR', 0, 'C', $fill);
                $this->Cell($w[1], 6, $row[1], 'LR', 0, 'L', $fill);
                $this->Cell($w[2], 6, $row[2], 'LR', 0, 'C', $fill);
                $this->Cell($w[3], 6, $row[3], 'LR', 0, 'C', $fill);
                $this->Cell($w[4], 6, $row[4], 'LR', 0, 'C', $fill);
                $this->Cell($w[5], 6, $row[5], 'LR', 0, 'C', $fill);
                $this->Cell($w[6], 6, $row[6], 'LR', 0, 'C', $fill);
                $this->Ln();
            }
            else
            {
                //Otherwise we are fine with this row, discard undo history.
                $this->commitTransaction();
            }
            $fill=!$fill;
        }
        $this->Cell(array_sum($w), 0, '', 'T');
    }
}
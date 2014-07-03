#!/bin/env ruby
# encoding: utf-8

require 'rubygems'
require 'prawn'
require 'json'
require 'logger'
require 'date'
require 'base64'

class KustannusarvioPDF
  @logo   = nil
  @margin = nil
  @data   = nil

  def initialize
    @margin = 20
  end

  def generate
    init

    #Filename is a separate variable because pdf.render_file wants full path but in HTML save form we want to force the directory user is able to download files from. this is the reason we only retrun filename
    filepath = "/tmp/Kustannusarvio_#{@data['tunnus'].to_s}.pdf"
    filename = "Kustannusarvio_#{@data['tunnus'].to_s}.pdf"

    Prawn::Document.generate(filepath,
                             { :page_size   => "A4",
                               :page_layout => :portrait,
                               :margin      => [@margin]
                             }) do |pdf|
      pdf.font 'Helvetica', :style => :normal, :size => 8

      header pdf

      info pdf

      rows pdf

      total pdf
    end

    filename
  end

  def init
    @logo   = @data['logo']
  end

  def header(pdf)
    pdf.repeat(:all, :dynamic => true) do
      logo pdf
      pdf.font 'Helvetica', :style => :bold, :size => 8
      pdf.draw_text "Ajalta/Tidsperiod #{@data['alku']} - #{@data['loppu']}", :at => [180, pdf.bounds.top - 20]
      pdf.font 'Helvetica', :style => :normal
    end
  end

  def info(pdf)
    pdf.move_down 100
    pdf.bounding_box([pdf.bounds.left, pdf.cursor], :width => pdf.bounds.right) do
      top_coordinate = pdf.cursor - 100
      pdf.font 'Helvetica', :style => :normal, :size => 10
      pdf.text @data['yhtio']['nimi']
    end
  end

  def rows(pdf)
    row_headers pdf
    pdf.move_down 10

    @data['rivit'].each do |i,row|
      row pdf, row
    end

    pdf.move_down 10
  end

  def row_headers(pdf)
    #Line defines the drawing path. Stroke actually draws the line

    pdf.move_down 10
    pdf.font 'Helvetica', :size => 10, :style => :bold
    pdf.text 'KUSTANNUSARVIO / KOSTNADSKALKYL'
    pdf.font 'Helvetica', :size => 7, :style => :bold

    pdf.float do
      pdf.text "Asiakas nro \nkund nr"
    end

    pdf.indent(90) do
      pdf.float do
        pdf.text "Laskutusasiakas \nFaktureringskund"
      end
    end

    pdf.indent(190) do
      pdf.float do
        pdf.text "Kohde \nPlats"
      end
    end

    pdf.indent(270) do
      pdf.float do
        pdf.text "Toimenpiteitä yht. \nÅtgärder total"
      end
    end

    pdf.move_down 15

    pdf.indent(380) do
      pdf.float do
        pdf.text "Tarkastuksia yht. \nGranskningar" , :rotate => 90
      end
    end

    pdf.indent(410) do
      pdf.float do
        pdf.text "Huoltoja yht. \nService" , :rotate => 90
      end
    end

    pdf.indent(440) do
      pdf.float do
        pdf.text "Painekokeita yht. \nTryckprov" , :rotate => 90
      end
    end

    pdf.move_up 15

    pdf.indent(480) do
      pdf.float do
        pdf.text "Laskutus/kohde yht. \Fakturerin total"
      end
    end

    pdf.move_down 30
    pdf.horizontal_line 0, 550
    pdf.stroke
  end

  def row(pdf, row)
    table_cells = [
        pdf.make_cell(:content => @data['asiakas']['tunnus']),
        pdf.make_cell(:content => @data['asiakas']['nimi']),
        pdf.make_cell(:content => row['kohde_nimi']),
        pdf.make_cell(:content => row['toimenpide_kpl']),
        pdf.make_cell(:content => row['tarkastus_kpl']),
        pdf.make_cell(:content => row['huolto_kpl']),
        pdf.make_cell(:content => row['koeponnistus_kpl']),
        pdf.make_cell(:content => row['hinta'])
    ]

    pdf.table([table_cells],
              :column_widths => {
                  0  => 90,
                  1  => 100,
                  2  => 90,
                  3  => 100,
                  4  => 30,
                  5  => 30,
                  6  => 40,
                  7  => 50
              },
              :cell_style    => {
                  :borders => []
              })
  end

  def total(pdf)
    pdf.horizontal_line 0, 550
    pdf.stroke
    pdf.move_down 10
    pdf.text_box "Yht.alv 0 %\nTot.moms 0 %", :at => [0, pdf.cursor], :align => :right, :width => 440
    pdf.text_box @data['total_hinta'], :at => [0, pdf.cursor], :align => :right, :width => 505
  end

  def logo(pdf)
    filepath = '/tmp/logo.jpeg'
    File.open(filepath, 'a+') { |file|
      file.write Base64.decode64 @logo
    }
    pdf.float do
      pdf.image filepath, :width => 139, :height => 76, :at => [pdf.bounds.left, pdf.bounds.top]
    end
  end

  def data=(data)
    @data = data
  end

end

class KustannusarvioDAO

  attr_accessor :data

  def initialize(filepath)
    @data = JSON.load(File.read(filepath))
  end

  def data
    @data
  end
end

if !ARGV[0].empty?

  kustannusarvio = KustannusarvioDAO.new(ARGV[0])

  pdf      = KustannusarvioPDF.new
  pdf.data = kustannusarvio.data

  puts pdf.generate
else
  #error
  #exit
end

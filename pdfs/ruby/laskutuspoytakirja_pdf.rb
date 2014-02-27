#!/bin/env ruby
# encoding: utf-8

require 'rubygems'
require 'prawn'
require 'json'
require 'logger'
require 'date'
require 'base64'

class LaskutuspoytakirjaPDF
  @logo = nil
  @margin = nil
  @data = nil
  @pdf = nil

  def initialize
    @margin = 20
  end

  def generate _pdf
    init
    @pdf = _pdf
    @pdf.font 'Helvetica', :style => :bold, :size => 8
    header
    info
    rows
    footer
    filename = "Laskutuspoytakirja.pdf"
  end

  def init
    @logo   = @data['logo']
  end

  def header
    @pdf.repeat(:all, :dynamic => true) do
      @pdf.draw_text @pdf.page_number, :at => [770, 540]
      #@pdf.draw_text "Laskutuspöytäkirjan nro", :at => [680, 520]
      #@pdf.draw_text "Faktureringsprotokoll nr", :at => [680, 510]
      logo
      @pdf.draw_text @data['yhtio']['nimi'], :at => [0, 465]
      @pdf.move_down 100
      @pdf.horizontal_rule
      @pdf.stroke
    end
  end

  def info
    @pdf.move_down 120
    @pdf.text_box "LASKUTUSPÖYTÄKIRJA / FAKTURERINGSPROTOKOLL", :at => [0, @pdf.cursor]
    #@pdf.text_box "Tarkastuspöytäkirjan nro", :at => [680, @pdf.cursor+5]
    #@pdf.text_box "Faktureringsprotokoll nr", :at => [680, @pdf.cursor-5]
    @pdf.move_down 20
    @pdf.horizontal_rule
    @pdf.stroke
    @pdf.move_down 20
    @pdf.text_box "Asiakas/Kund", :at => [0, @pdf.cursor]
    @pdf.text_box @data['asiakas']['nimi'], :at => [120, @pdf.cursor]
    @pdf.text_box "Asiakasnro./Kund nr. #{@data['kohde']['tunnus']}", :at => [400, @pdf.cursor]
    @pdf.move_down 20
    @pdf.horizontal_rule
    @pdf.stroke
    @pdf.move_down 20
    @pdf.text_box "Kohde/Plats", :at => [0, @pdf.cursor]
    @pdf.text_box @data['kohde']['nimi'], :at => [120, @pdf.cursor]
    @pdf.text_box "Kust.paikka/Merkki", :at => [@pdf.bounds.right-500, @pdf.cursor+5], :width => 100, :align => :right
    @pdf.text_box "Kostnadsställe/Märke", :at => [@pdf.bounds.right-500, @pdf.cursor-5], :width => 100, :align => :right
    @pdf.text_box "Tilausnumero/", :at => [600, @pdf.cursor+5]
    @pdf.text_box "Order nr", :at => [600, @pdf.cursor-5]
    @pdf.move_down 20
    @pdf.horizontal_rule
    @pdf.stroke
  end

  def rows
    row_headers
    @data['rivit'].each_with_index do |r, index|
      row(r)
      if ( @pdf.cursor < 60)
        @pdf.start_new_page
        @pdf.move_down 100
        row_headers
      end
    end
  end

  def row_headers
    @pdf.move_down 20
    @pdf.text_box "Tuote nro. / Produkt nr.", :at => [0, @pdf.cursor]
    @pdf.text_box "Nimike / Produkt", :at => [120, @pdf.cursor]
    @pdf.text_box "Kpl. / St.", :at => [@pdf.bounds.right-500, @pdf.cursor], :width => 100, :align => :right
    @pdf.text_box "á-hinta / á-pris", :at => [@pdf.bounds.right-400, @pdf.cursor], :width => 100, :align => :right
    @pdf.text_box "Alennus % / Rabatt %", :at => [@pdf.bounds.right-300, @pdf.cursor], :width => 100, :align => :right
    @pdf.text_box "Yhteensä / Totalt", :at => [@pdf.bounds.right-200, @pdf.cursor], :width => 100, :align => :right
    @pdf.move_down 20
    @pdf.horizontal_rule
    @pdf.stroke
    @pdf.move_down 20
  end

  def row(rivi)
    @pdf.font 'Helvetica', :style => :normal, :size => 8
    @pdf.text_box rivi['tuoteno'], :at => [0, @pdf.cursor]
    @pdf.text_box rivi['nimitys'], :at => [120, @pdf.cursor]
    @pdf.text_box rivi['kpl'], :at => [@pdf.bounds.right-500, @pdf.cursor], :width => 100, :align => :right
    @pdf.text_box rivi['hinta'], :at => [@pdf.bounds.right-400, @pdf.cursor], :width => 100, :align => :right
    @pdf.text_box rivi['ale1'], :at => [@pdf.bounds.right-300, @pdf.cursor], :width => 100, :align => :right
    @pdf.text_box rivi['total'], :at => [@pdf.bounds.right-200, @pdf.cursor], :width => 100, :align => :right
    @pdf.move_down 20
  end

  def footer
    @pdf.line [@pdf.bounds.left, 60], [@pdf.bounds.right, 60]
    @pdf.text_box "Yht. alv / Totalt moms 0 %", :at => [@pdf.bounds.right-200, 50], :width => 100, :align => :right
    @pdf.text_box "alv / moms 23,00 %", :at => [@pdf.bounds.right-200, 36], :width => 100, :align => :right
    @pdf.text_box "Yhteensä alv / Totalt moms 23,00 %", :at => [@pdf.bounds.right-200, 10], :width => 100, :align => :right

    @pdf.font 'Helvetica', :style => :bold, :size => 8
    @pdf.text_box @data['full_total'], :at => [@pdf.bounds.right-90, 50], :align => :right

    alv = (@data['full_total'].to_i * 0.23).round(2)
    @pdf.text_box alv.to_s, :at => [@pdf.bounds.right-90, 36], :align => :right

    total_with_alv = (@data['full_total'].to_i + alv)
    @pdf.text_box total_with_alv.to_s, :at => [@pdf.bounds.right-90, 10], :align => :right

    @pdf.font 'Helvetica', :style => :normal, :size => 8
    @pdf.line [@pdf.bounds.left, 20], [@pdf.bounds.right, 20]
  end

  def logo
    filepath = '/tmp/logo.jpeg'
    File.open(filepath, 'a+') { |file|
      file.write Base64.decode64 @logo
    }
    @pdf.float do
      @pdf.image filepath, :scale => 0.7, :at => [0, 555.28]
    end
  end

  def data=(data)
    @data = data
  end

end

if !ARGV[0].empty?

  @data = JSON.load(File.read(ARGV[0]))

      file          = ''
      margin        = 20
      _pdf          = Prawn::Document.new(:page_size   => 'A4',
                                          :page_layout => :landscape,
                                          :margin      => margin
      )

    pdf      = LaskutuspoytakirjaPDF.new
    pdf.data = @data
    file = pdf.generate _pdf
    _pdf.render_file "/tmp/#{file}"
    puts file

end

#!/bin/env ruby
# encoding: utf-8

require 'rubygems'
require 'prawn'
require 'json'
require 'logger'
require 'date'
require 'base64'

class LaskutuspoytakirjaPDF
  @logo   = nil
  @margin = nil
  @data   = nil
  @pdf    = nil

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

    filename = "Laskutuspoytakirja.pdf"
  end

  def init
    @logo = @data['logo']
  end

  def header
    @pdf.repeat(:all, :dynamic => true) do
      @pdf.draw_text "#{@pdf.page_number}  (#{@pdf.page_count})", :at => [770, 540]

      logo

      @pdf.draw_text @data['yhtio']['nimi'], :at => [0, 465]

      @pdf.move_down 100

      @pdf.horizontal_rule
      @pdf.stroke
    end
  end

  def info
    @pdf.move_down 120

    @pdf.text "LASKUTUSPÖYTÄKIRJA / FAKTURERINGSPROTOKOLL"

    @pdf.move_down 15

    @pdf.horizontal_rule
    @pdf.stroke

    @pdf.move_down 15

    @pdf.float do
      @pdf.text "Asiakas/Kund"
    end

    @pdf.float do
      @pdf.indent(100) do
        @pdf.text @data['asiakas']['nimi']
      end
    end

    @pdf.float do
      @pdf.indent(250) do
        @pdf.text "Asiakasnro./Kund nr. #{@data['asiakas']['asiakasnro']}"
      end
    end

    @pdf.move_down 20

    @pdf.horizontal_rule
    @pdf.stroke

    @pdf.move_down 15

    @pdf.float do
      @pdf.text "Kohde/Plats"
    end

    @pdf.float do
      @pdf.indent(100) do
        @pdf.text @data['kohde']['nimi']
      end
    end

    @pdf.move_down 20

    @pdf.horizontal_rule
    @pdf.stroke
  end

  def rows
    row_headers

    @pdf.move_down 15.76

    @data['rivit'].each_with_index do |r, i|

      bottom = @data['rivit'].size-1 == i ? 80 : 10

      if @pdf.cursor < bottom
        @pdf.start_new_page
        @pdf.move_down 100.28
        row_headers
        @pdf.move_down 15
      end

      row(r)

      if @data['rivit'].size-1 == i
        footer
      end
    end
  end

  def row_headers
    @pdf.move_down 10

    @pdf.float do
      @pdf.text "Tuote nro. / Produkt nr."
    end

    @pdf.float do
      @pdf.indent(100) do
        @pdf.text "Nimike / Produkt"
      end
    end

    @pdf.float do
      @pdf.indent(350) do
        @pdf.text "Kpl. / St."
      end
    end

    @pdf.float do
      @pdf.indent(450) do
        @pdf.text "á-hinta / á-pris"
      end
    end

    @pdf.float do
      @pdf.indent(550) do
        @pdf.text "Alennus % / Rabatt %"
      end
    end

    @pdf.float do
      @pdf.indent(650) do
        @pdf.text "Yhteensä / Totalt"
      end
    end

    @pdf.move_down 15
    @pdf.horizontal_rule
    @pdf.stroke
  end

  def row(r)
    @pdf.font 'Helvetica', :style => :normal, :size => 8
    @pdf.float do
      @pdf.text r['tuoteno']
    end

    @pdf.float do
      @pdf.indent(100) do
        @pdf.text r['nimitys']
      end
    end

    @pdf.float do
      @pdf.indent(350) do
        @pdf.text r['kpl']
      end
    end

    @pdf.float do
      @pdf.indent(450) do
        @pdf.text r['hinta']
      end
    end

    @pdf.float do
      @pdf.indent(550) do
        @pdf.text r['alennusprosentti']
      end
    end

    @pdf.float do
      @pdf.indent(650) do
        @pdf.text r['hinta_yhteensa']
      end
    end

    @pdf.move_down 15
  end

  def footer
    @pdf.line [@pdf.bounds.left, 60], [@pdf.bounds.right, 60]

    @pdf.text_box "Yht. alv / Totalt moms 0 %", :at => [500, 50], :width => 100, :align => :right
    @pdf.text_box "alv / moms #{@data['alv_prosentti']} %", :at => [500, 36], :width => 100, :align => :right
    @pdf.text_box "Yhteensä alv / Totalt moms", :at => [500, 10], :width => 100, :align => :right

    @pdf.font 'Helvetica', :style => :bold, :size => 8
    @pdf.text_box @data['kaikki_yhteensa'], :at => [500, 50], :width => 170, :align => :right

    @pdf.text_box @data['alv_maara_yhteensa'], :at => [500, 36], :width => 170, :align => :right

    total_with_alv = (@data['kaikki_yhteensa'].to_f + @data['alv_maara_yhteensa'].to_f)
    @pdf.text_box total_with_alv.to_s, :at => [500, 10], :width => 170, :align => :right

    @pdf.line [@pdf.bounds.left, 20], [@pdf.bounds.right, 20]
  end

  def logo
    filepath = '/tmp/logo.jpeg'
    File.open(filepath, 'w+') { |file|
      file.write Base64.decode64 @logo
    }
    @pdf.float do
      @pdf.image filepath, :width => 139, :height => 76, :at => [0, 555.28]
    end
  end

  def data=(data)
    @data = data
  end

end

if !ARGV[0].empty?

  @data = JSON.load(File.read(ARGV[0]))

  file   = ''
  margin = 20
  _pdf   = Prawn::Document.new(:page_size   => 'A4',
                               :page_layout => :landscape,
                               :margin      => margin
  )

  pdf      = LaskutuspoytakirjaPDF.new
  pdf.data = @data
  file     = pdf.generate _pdf
  _pdf.render_file "/tmp/#{file}"
  puts file

end

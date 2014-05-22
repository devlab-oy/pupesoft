#!/bin/env ruby
# encoding: utf-8

require 'rubygems'
require 'prawn'
require 'json'
require 'logger'
require 'date'
require 'base64'

class SiirtokehotusPDF
  @margin = nil
  @data   = nil
  @pdf    = nil

  def initialize
    @margin = 20
  end

  def generate _pdf
    @pdf = _pdf
    rows
    filename = "siirtokehotus.pdf"
  end

  def rows

    row_headers
    @data.each_with_index do |r, i|
      @vp_size = r['varapaikat'].size
      if @pdf.cursor < (15 + @vp_size * 15)
        @pdf.start_new_page
        row_headers
      end
      oletus_row(r, i)
    end
  end

  def row_headers
    @pdf.font 'Helvetica', :style => :bold, :size => 8
    @pdf.float do
      @pdf.text "Tyyppi"
    end
    @pdf.float do
      @pdf.indent(100) do
        @pdf.text "Tuotenumero"
      end
    end
    @pdf.float do
      @pdf.indent(200) do
        @pdf.text "Tuotepaikka"
      end
    end
    @pdf.float do
      @pdf.indent(300) do
        @pdf.text "Hyllyssä"
      end
    end
    @pdf.float do
      @pdf.indent(400) do
        @pdf.text "Hälytysraja"
      end
    end
    @pdf.font 'Helvetica', :style => :normal, :size => 8
    @pdf.move_down 15
    @pdf.horizontal_rule
    @pdf.stroke
    @pdf.move_down 10
  end

  def oletus_row(r, i)
    @pdf.float do
      @pdf.text "Oletuspaikka"
    end
    @pdf.float do
      @pdf.indent(100) do
        @pdf.text r['tuoteno']
      end
    end
    @pdf.float do
      @pdf.indent(200) do
        @pdf.text r['tuotepaikka']
      end
    end
    @pdf.float do
      @pdf.indent(300) do
        @pdf.text r['hyllyssa']
      end
    end
    @pdf.float do
      @pdf.indent(400) do
        @pdf.text r['haly']
      end
    end
    @pdf.move_down 15
    r['varapaikat'].each_with_index do |r, i|
      vara_row(r, i)
    end
  end

  def vara_row(r, i)
    @pdf.float do
      @pdf.text "Varapaikka"
    end
    @pdf.float do
      @pdf.indent(200) do
        @pdf.text r['tuotepaikka']
      end
    end
    @pdf.float do
      @pdf.indent(300) do
        @pdf.text r['hyllyssa']
      end
    end

    if @vp_size-1 == i
      @pdf.move_down 15
      @pdf.horizontal_rule
      @pdf.stroke
    end
    @pdf.move_down 15
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
                               :page_layout => :portrait,
                               :margin      => margin
  )

  pdf      = SiirtokehotusPDF.new
  pdf.data = @data
  file     = pdf.generate _pdf
  _pdf.render_file "/tmp/#{file}"
  puts file

end

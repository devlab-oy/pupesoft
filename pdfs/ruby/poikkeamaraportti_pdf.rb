#!/bin/env ruby
# encoding: utf-8

require 'rubygems'
require 'prawn'
require 'json'
require 'logger'
require 'date'
require 'base64'

#@TODO If poikkeamaraportti prints many rows the page break doesnt work as wanted. All the other data except rows need to go inside header so that all the other data gets printed when page break occurs.

class PoikkeamaraporttiPDF

  attr_accessor :customer, :company

  def initialize
    margin = 20
    @pdf = Prawn::Document.new(:page_size => "A4", :page_layout => :landscape, :margin => [margin, margin, margin, margin])

    @pdf.font 'Helvetica', :style => :normal, :size => 10

    #NOTICE!! We have to use pdf_x and pdf_y because Prawn has x and y variables in it and they mix with this class x and y if we had used them in @pdf.repeat
    @pdf.repeat(:all, :dynamic => true) do
      @pdf.draw_text @pdf.page_number, :at => [770, 520]

      @pdf_x = 0
      @pdf_y = 560
      self.header
    end
  end

  def data=(data)
    @data = data
  end

  def generate
    if @data.nil?
      #error
      #exit
    end

    #NOTICE!!! We have to manually set the y to correct spot because self.header gets called at @pdf.render_file method. because of this we do not know how long the header is
    @x = 0
    @y = 410
    @pdf.font 'Helvetica', :size => 7, :style => :bold

    @y -= 110
    @pdf.move_down(110)
    self.print_table

    filepath = "/tmp/Poikkeamaraportti_" + @data['tunnus'].to_s + ".pdf"
    #Filename is a separate variable because pdf.render_file wants full path but in HTML save form we want to force the directory user is able to download files from. this is the reason we only retrun filename
    filename = "Poikkeamaraportti_" + @data['tunnus'].to_s + ".pdf";

    @pdf.render_file filepath

    return filename
  end

  def print_table
    customer_table = @pdf.make_table([
      [
        @pdf.make_cell(:content => 'Asiakas/Kund', :width => 150),
        @pdf.make_cell(:content => @customer['nimi'], :font_style => :normal)
      ]
    ], :width => @pdf.bounds.right, :cell_style => { :borders => [] })

    contact_person_table = @pdf.make_table([
      [
        @pdf.make_cell(:content => 'Yhteyshenkilö/Kontaktperson', :width => 150),
        @pdf.make_cell(:content => @data['kohde']['yhteyshlo'], :font_style => :normal, :width => 400),
        @pdf.make_cell(:content => '')
      ]
    ], :width => @pdf.bounds.right, :cell_style => { :borders => [] })

    performer_table = @pdf.make_table([
      [
        @pdf.make_cell(:content => 'Työnsuorittaja/Arbetetsutförare', :width => 150),
        @pdf.make_cell(:content => @data['tyon_suorittaja'], :font_style => :normal, :width => 400),
        @pdf.make_cell(:content => 'Päiväys/Datum', :width => 100),
        @pdf.make_cell(:content => Time.new.strftime('%d.%m.%Y') , :font_style => :normal),
        @pdf.make_cell(:content => '')
      ]
    ], :width => @pdf.bounds.right, :cell_style => { :borders => [] })

    table1 = @pdf.make_table([
      [
        @pdf.make_cell(:content => 'Kohde/Plats', :width => 150),
        @pdf.make_cell(:content => 'Sijainti/Placering', :width => 150),
        @pdf.make_cell(:content => 'Kalusto/Produkt', :width => 100),
        @pdf.make_cell(:content => 'Numero/Nummer' + "\n" + 'Sarjanumero/Radnummer', :width => 100),
        @pdf.make_cell(:content => 'Poikkeama ja toimenpide/Avvikelse och åtgärd')
      ]
    ], :width => @pdf.bounds.right)

    rows_table = self.rows_table

    toimitettuaika = Date.parse(@data['toimitettuaika'])
    table2 = @pdf.make_table([
      [
        @pdf.make_cell(:content => 'Pvm', :width => 100, :height => 25),
        @pdf.make_cell(:content => 'Työn suorittajan kuittaus / nimen selvennys Arbetsutförares kvittering / namnförtydligande', :height => 25),
      ],
      [
        @pdf.make_cell(:content => toimitettuaika.strftime('%d.%m.%Y'), :width => 100, :height => 25),
        @pdf.make_cell(:content => @data['tyon_suorittaja'], :height => 25),
      ]
    ], :width => @pdf.bounds.right, :cell_style => { :borders => [:right] })

    table_data = [
      [customer_table],
      [contact_person_table],
      [performer_table],
      [table1],
      [@pdf.make_cell(:content => rows_table, :height => 300)],
      [table2]
     ]

    @pdf.table(table_data, :width => @pdf.bounds.right)
  end

  def rows_table
    rows = []
    @data['rivit'].each do |value|
      #@pdf.make_cell doesnt handel nil values so we need to pass empty string to it if nil
      exception_text = (value['poikkeus_teksti'].nil?) ? '' : value['poikkeus_teksti']
      exception_text += "\n"
      exception_text += (value['kommentti'].nil?) ? '' : value['kommentti']

      own_number_and_serial_number = (value['laite']['oma_numero'].nil?) ? '' : value['laite']['oma_numero']
      own_number_and_serial_number += "\n"
      own_number_and_serial_number += (value['laite']['sarjanro'].nil?) ? '' : value['laite']['sarjanro']
      row = [
        @pdf.make_cell(:content => (@data['kohde']['nimi'].nil?) ? '' : @data['kohde']['nimi'], :width => 150, :font_style => :normal),
        @pdf.make_cell(:content => (value['laite']['sijainti'].nil?) ? '' : value['laite']['sijainti'], :width => 150, :font_style => :normal),
        @pdf.make_cell(:content => (value['laite']['tuoteno'].nil?) ? '' : value['laite']['tuoteno'], :width => 100, :font_style => :normal),
        @pdf.make_cell(:content => own_number_and_serial_number, :width => 100, :font_style => :normal),
        @pdf.make_cell(:content => exception_text, :font_style => :normal)
      ]

      rows << row
    end
    return @pdf.make_table(rows, :width => @pdf.bounds.right, :cell_style => { :borders => [] })
  end

  def header
    self.logo

    @pdf_y -= 90
    self.company_info
  end

  def logo
    file = File.new('/tmp/logo.jpeg', 'w+')
    file.write Base64.decode64 @data['logo']
    file.close
    @pdf.image file.path, :width => 139, :height => 76, :at => [@pdf_x, @pdf_y]
  end

  def company_info
    @pdf.font 'Helvetica', :size => 10
    @pdf.draw_text @company['nimi'], :at => [@pdf_x, @pdf_y]

    @pdf_y -= 20
    @pdf.draw_text 'POIKKEAMARAPORTTI / AVVIKELSERAPPORT', :at => [@pdf_x, @pdf_y], :style => :bold
  end
end

class WorkOrderDAO

  attr_accessor :data

  def initialize(filepath)
    self.fetch_data(filepath)
  end

  def fetch_data(filepath)
    @data = JSON.load(File.read(filepath))
  end

  def data
    return @data
  end
end

if !ARGV[0].nil?

  workorder = WorkOrderDAO.new(ARGV[0])

  pdf = PoikkeamaraporttiPDF.new
  pdf.customer = workorder.data['asiakas']
  pdf.company = workorder.data['yhtio']
  pdf.data = workorder.data
  p pdf.generate
else
  #error
  #exit
end

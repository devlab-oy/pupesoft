#!/bin/env ruby
# encoding: utf-8

require 'rubygems'
require 'prawn'
require 'json'
require 'logger'
require 'date'
require 'base64'

class KalustoraporttiPDF
  @logo     = nil
  @customer = nil
  @company  = nil

  @margin = nil
  @data   = nil

  attr_accessor :customer, :company, :logo

  def initialize
    @margin = 20
  end

  def generate
    #Filename is a separate variable because pdf.render_file wants full path but in HTML save form we want to force the directory user is able to download files from. this is the reason we only retrun filename
    filepath = "/tmp/Kalustoraportti_#{@data['kohde_tunnus'].to_s}.pdf"
    filename = "Kalustoraportti_#{@data['kohde_tunnus'].to_s}.pdf"

    Prawn::Document.generate(filepath, { :page_size => "A4", :page_layout => :landscape, :margin => [@margin, @margin, @margin, @margin] }) do |pdf|
      pdf.font 'Helvetica', :style => :normal, :size => 8
      header pdf

      info pdf
    end

    return filename
  end

  def info(pdf)
    pdf.font 'Helvetica', :size => 10
    pdf.text @company['nimi']
  end

  def header(pdf)
    pdf.repeat(:all, :dynamic => true) do
      pdf.draw_text pdf.page_number, :at => [770, 520]

      logo pdf
    end
  end

  def logo(pdf)
    filepath = '/tmp/logo.jpeg'
    File.open(filepath, 'a+') { |file|
      file.write Base64.decode64 @logo
    }
    pdf.image filepath, :scale => 0.7
  end

  def data=(data)
    @data = data
  end
end

class SpotDAO

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

if !ARGV[0].empty?

  spots = SpotDAO.new(ARGV[0])

  files = ''
  spots.data['kohteet'].each do |index, spot|
    pdf          = KalustoraporttiPDF.new
    pdf.customer = spots.data['asiakas']
    pdf.company  = spots.data['yhtio']
    pdf.logo     = spots.data['logo']
    pdf.data     = spot

    files += pdf.generate + ' '
  end
  puts files
else
  puts 'argv0 is empty'
end
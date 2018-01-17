<?php

define("lavakerayskapasiteetti", 1488);

$lavakeraysparam = "if ((tuote.mallitarkenne = '400' and tuote.myynti_era='1'), 8,
                      if (tuote.mallitarkenne = '200', 7,
                        if (tuote.mallitarkenne = '12', 6,
                          if (tuote.mallitarkenne = '1000', 5,
                            if (tuote.mallitarkenne = '150', 4,
                              if (tuote.mallitarkenne = '585', 3,
                                if (tuote.mallitarkenne = '500', 2,
                                  if (tuote.mallitarkenne = '400', 1, 9)
                                )
                              )
                            )
                          )
                        )
                      )
                    ) lavasort,
                    if (tuote.mallitarkenne = '200', 0.57,
                      if (tuote.mallitarkenne = '12', 0.36,
                        if (tuote.mallitarkenne = '1000', 2.07,
                          if (tuote.mallitarkenne = '150', 0.50,
                            if (tuote.mallitarkenne = '585', 1.14,
                              if (tuote.mallitarkenne = '500', 1.14,
                                if (tuote.mallitarkenne = '400', 1, 1.07)
                              )
                            )
                          )
                        )
                      )
                    ) lavakoko, ";

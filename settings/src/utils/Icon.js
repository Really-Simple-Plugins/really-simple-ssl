const Icon = (props) => {
  const {name, color, size} = props;
  // set defaults if not se

  const iconName = name || 'bullet';
  const iconColor = color || 'black';
  const iconSize = size || 15;
  const iconColors = {
    'black': 'var(--rsp-black)',
    'green': 'var(--rsp-green)',
    'yellow': 'var(--rsp-yellow)',
    'orange': 'var(--rsp-yellow)',
    'red': 'var(--rsp-red)',
    'grey': 'var(--rsp-grey-400)',
  };
  let renderedIcon = '';

  if (iconName === 'bullet') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256z"/>
          </svg>,
    };
  }

  if (iconName === 'circle') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256zM256 48C141.1 48 48 141.1 48 256C48 370.9 141.1 464 256 464C370.9 464 464 370.9 464 256C464 141.1 370.9 48 256 48z"/>
          </svg>,
    };
  }

  if (iconName === 'check') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256zM256 48C141.1 48 48 141.1 48 256C48 370.9 141.1 464 256 464C370.9 464 464 370.9 464 256C464 141.1 370.9 48 256 48z"/>
          </svg>,
    };
  }

  if (iconName === 'warning') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M506.3 417l-213.3-364c-16.33-28-57.54-28-73.98 0l-213.2 364C-10.59 444.9 9.849 480 42.74 480h426.6C502.1 480 522.6 445 506.3 417zM232 168c0-13.25 10.75-24 24-24S280 154.8 280 168v128c0 13.25-10.75 24-23.1 24S232 309.3 232 296V168zM256 416c-17.36 0-31.44-14.08-31.44-31.44c0-17.36 14.07-31.44 31.44-31.44s31.44 14.08 31.44 31.44C287.4 401.9 273.4 416 256 416z"/>
          </svg>,
    };
  }
  if (iconName === 'error') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M256 0C114.6 0 0 114.6 0 256s114.6 256 256 256s256-114.6 256-256S397.4 0 256 0zM232 152C232 138.8 242.8 128 256 128s24 10.75 24 24v128c0 13.25-10.75 24-24 24S232 293.3 232 280V152zM256 400c-17.36 0-31.44-14.08-31.44-31.44c0-17.36 14.07-31.44 31.44-31.44s31.44 14.08 31.44 31.44C287.4 385.9 273.4 400 256 400z"/>
          </svg>,
    };
  }

  if (iconName === 'times') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M310.6 361.4c12.5 12.5 12.5 32.75 0 45.25C304.4 412.9 296.2 416 288 416s-16.38-3.125-22.62-9.375L160 301.3L54.63 406.6C48.38 412.9 40.19 416 32 416S15.63 412.9 9.375 406.6c-12.5-12.5-12.5-32.75 0-45.25l105.4-105.4L9.375 150.6c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L160 210.8l105.4-105.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-105.4 105.4L310.6 361.4z"/>
          </svg>,
    };
  }

  if (iconName === 'circle-check') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256zM371.8 211.8C382.7 200.9 382.7 183.1 371.8 172.2C360.9 161.3 343.1 161.3 332.2 172.2L224 280.4L179.8 236.2C168.9 225.3 151.1 225.3 140.2 236.2C129.3 247.1 129.3 264.9 140.2 275.8L204.2 339.8C215.1 350.7 232.9 350.7 243.8 339.8L371.8 211.8z"/>
          </svg>,
    };
  }

  if (iconName === 'circle-times') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256zM175 208.1L222.1 255.1L175 303C165.7 312.4 165.7 327.6 175 336.1C184.4 346.3 199.6 346.3 208.1 336.1L255.1 289.9L303 336.1C312.4 346.3 327.6 346.3 336.1 336.1C346.3 327.6 346.3 312.4 336.1 303L289.9 255.1L336.1 208.1C346.3 199.6 346.3 184.4 336.1 175C327.6 165.7 312.4 165.7 303 175L255.1 222.1L208.1 175C199.6 165.7 184.4 165.7 175 175C165.7 184.4 165.7 199.6 175 208.1V208.1z"/>
          </svg>,
    };
  }

  if (iconName === 'chevron-up') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M416 352c-8.188 0-16.38-3.125-22.62-9.375L224 173.3l-169.4 169.4c-12.5 12.5-32.75 12.5-45.25 0s-12.5-32.75 0-45.25l192-192c12.5-12.5 32.75-12.5 45.25 0l192 192c12.5 12.5 12.5 32.75 0 45.25C432.4 348.9 424.2 352 416 352z"/>
          </svg>,
    };
  }

  if (iconName === 'chevron-down') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M224 416c-8.188 0-16.38-3.125-22.62-9.375l-192-192c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L224 338.8l169.4-169.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-192 192C240.4 412.9 232.2 416 224 416z"/>
          </svg>,
    };
  }

  if (iconName === 'chevron-right') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M96 480c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L242.8 256L73.38 86.63c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l192 192c12.5 12.5 12.5 32.75 0 45.25l-192 192C112.4 476.9 104.2 480 96 480z"/>
          </svg>,
    };
  }

  if (iconName === 'chevron-left') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M224 480c-8.188 0-16.38-3.125-22.62-9.375l-192-192c-12.5-12.5-12.5-32.75 0-45.25l192-192c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25L77.25 256l169.4 169.4c12.5 12.5 12.5 32.75 0 45.25C240.4 476.9 232.2 480 224 480z"/>
          </svg>,
    };
  }

  if (iconName === 'plus') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M432 256c0 17.69-14.33 32.01-32 32.01H256v144c0 17.69-14.33 31.99-32 31.99s-32-14.3-32-31.99v-144H48c-17.67 0-32-14.32-32-32.01s14.33-31.99 32-31.99H192v-144c0-17.69 14.33-32.01 32-32.01s32 14.32 32 32.01v144h144C417.7 224 432 238.3 432 256z"/>
          </svg>,
    };
  }

  if (iconName === 'minus') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M400 288h-352c-17.69 0-32-14.32-32-32.01s14.31-31.99 32-31.99h352c17.69 0 32 14.3 32 31.99S417.7 288 400 288z"/>
          </svg>,
    };
  }

  if (iconName === 'sync') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M483.515 28.485L431.35 80.65C386.475 35.767 324.485 8 256 8 123.228 8 14.824 112.338 8.31 243.493 7.971 250.311 13.475 256 20.301 256h28.045c6.353 0 11.613-4.952 11.973-11.294C66.161 141.649 151.453 60 256 60c54.163 0 103.157 21.923 138.614 57.386l-54.128 54.129c-7.56 7.56-2.206 20.485 8.485 20.485H492c6.627 0 12-5.373 12-12V36.971c0-10.691-12.926-16.045-20.485-8.486zM491.699 256h-28.045c-6.353 0-11.613 4.952-11.973 11.294C445.839 370.351 360.547 452 256 452c-54.163 0-103.157-21.923-138.614-57.386l54.128-54.129c7.56-7.56 2.206-20.485-8.485-20.485H20c-6.627 0-12 5.373-12 12v143.029c0 10.691 12.926 16.045 20.485 8.485L80.65 431.35C125.525 476.233 187.516 504 256 504c132.773 0 241.176-104.338 247.69-235.493.339-6.818-5.165-12.507-11.991-12.507z"></path>
          </svg>,
    };
  }

  if (iconName === 'sync-error') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M256 79.1C178.5 79.1 112.7 130.1 89.2 199.7C84.96 212.2 71.34 218.1 58.79 214.7C46.23 210.5 39.48 196.9 43.72 184.3C73.6 95.8 157.3 32 256 32C337.5 32 408.8 75.53 448 140.6V104C448 90.75 458.7 80 472 80C485.3 80 496 90.75 496 104V200C496 213.3 485.3 224 472 224H376C362.7 224 352 213.3 352 200C352 186.7 362.7 176 376 176H412.8C383.7 118.1 324.4 80 256 80V79.1zM280 263.1C280 277.3 269.3 287.1 256 287.1C242.7 287.1 232 277.3 232 263.1V151.1C232 138.7 242.7 127.1 256 127.1C269.3 127.1 280 138.7 280 151.1V263.1zM224 352C224 334.3 238.3 319.1 256 319.1C273.7 319.1 288 334.3 288 352C288 369.7 273.7 384 256 384C238.3 384 224 369.7 224 352zM40 432C26.75 432 16 421.3 16 408V311.1C16 298.7 26.75 287.1 40 287.1H136C149.3 287.1 160 298.7 160 311.1C160 325.3 149.3 336 136 336H99.19C128.3 393 187.6 432 256 432C333.5 432 399.3 381.9 422.8 312.3C427 299.8 440.7 293 453.2 297.3C465.8 301.5 472.5 315.1 468.3 327.7C438.4 416.2 354.7 480 256 480C174.5 480 103.2 436.5 64 371.4V408C64 421.3 53.25 432 40 432V432z"/>
          </svg>,
    };
  }

  if (iconName === 'shortcode') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M128 32H32C14.4 32 0 46.4 0 64v384c0 17.6 14.4 32 32 32h96C145.7 480 160 465.7 160 448S145.7 416 128 416H64V96h64C145.7 96 160 81.67 160 64S145.7 32 128 32zM416 32h-96C302.3 32 288 46.33 288 63.1S302.3 96 319.1 96H384v320h-64C302.3 416 288 430.3 288 447.1S302.3 480 319.1 480H416c17.6 0 32-14.4 32-32V64C448 46.4 433.6 32 416 32z"/>
          </svg>,
    };
  }

  if (iconName === 'file') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M0 64C0 28.65 28.65 0 64 0H229.5C246.5 0 262.7 6.743 274.7 18.75L365.3 109.3C377.3 121.3 384 137.5 384 154.5V448C384 483.3 355.3 512 320 512H64C28.65 512 0 483.3 0 448V64zM336 448V160H256C238.3 160 224 145.7 224 128V48H64C55.16 48 48 55.16 48 64V448C48 456.8 55.16 464 64 464H320C328.8 464 336 456.8 336 448z"/>
          </svg>,
    };
  }

  if (iconName === 'file-disabled') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M639.1 487.1c0-7.119-3.153-14.16-9.191-18.89l-118.8-93.12l.0013-237.3c0-16.97-6.742-33.26-18.74-45.26l-74.63-74.64C406.6 6.742 390.3 0 373.4 0H192C156.7 0 128 28.65 128 64L128 75.01L38.82 5.11C34.41 1.672 29.19 0 24.04 0C10.19 0-.0002 11.3-.0002 23.1c0 7.12 3.153 14.16 9.192 18.89l591.1 463.1C605.6 510.3 610.8 512 615.1 512C629.8 512 639.1 500.6 639.1 487.1zM464 338.4l-287.1-225.7l-.002-48.51c0-8.836 7.164-16 15.1-16h160l-.0065 79.87c0 17.67 14.33 31.1 31.1 31.1L464 159.1V338.4zM448 463.1H192c-8.834 0-15.1-7.164-15.1-16L176 234.6L128 197L128 447.1c0 35.34 28.65 64 63.1 64H448c20.4 0 38.45-9.851 50.19-24.84l-37.72-29.56C457.5 461.4 453.2 463.1 448 463.1z"/>
          </svg>,
    };
  }

  if (iconName === 'file-download') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M216 342.1V240c0-13.25-10.75-24-24-24S168 226.8 168 240v102.1L128.1 303C124.3 298.3 118.2 296 112 296S99.72 298.3 95.03 303c-9.375 9.375-9.375 24.56 0 33.94l80 80c9.375 9.375 24.56 9.375 33.94 0l80-80c9.375-9.375 9.375-24.56 0-33.94s-24.56-9.375-33.94 0L216 342.1zM365.3 93.38l-74.63-74.64C278.6 6.742 262.3 0 245.4 0H64C28.65 0 0 28.65 0 64l.0065 384c0 35.34 28.65 64 64 64H320c35.2 0 64-28.8 64-64V138.6C384 121.7 377.3 105.4 365.3 93.38zM336 448c0 8.836-7.164 16-16 16H64.02c-8.838 0-16-7.164-16-16L48 64.13c0-8.836 7.164-16 16-16h160L224 128c0 17.67 14.33 32 32 32h79.1V448z"/>
          </svg>,
    };
  }

  if (iconName === 'calendar') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M152 64H296V24C296 10.75 306.7 0 320 0C333.3 0 344 10.75 344 24V64H384C419.3 64 448 92.65 448 128V448C448 483.3 419.3 512 384 512H64C28.65 512 0 483.3 0 448V128C0 92.65 28.65 64 64 64H104V24C104 10.75 114.7 0 128 0C141.3 0 152 10.75 152 24V64zM48 448C48 456.8 55.16 464 64 464H384C392.8 464 400 456.8 400 448V192H48V448z"/>
          </svg>,
    };
  }

  if (iconName === 'calendar-error') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M151.1 64H296V24C296 10.75 306.7 0 320 0C333.3 0 344 10.75 344 24V64H384C419.3 64 448 92.65 448 128V192H47.1V448C47.1 456.8 55.16 464 63.1 464H284.5C296.7 482.8 312.5 499.1 330.8 512H64C28.65 512 0 483.3 0 448V128C0 92.65 28.65 64 64 64H104V24C104 10.75 114.7 0 128 0C141.3 0 152 10.75 152 24L151.1 64zM576 368C576 447.5 511.5 512 432 512C352.5 512 287.1 447.5 287.1 368C287.1 288.5 352.5 224 432 224C511.5 224 576 288.5 576 368zM432 416C418.7 416 408 426.7 408 440C408 453.3 418.7 464 432 464C445.3 464 456 453.3 456 440C456 426.7 445.3 416 432 416zM447.1 288C447.1 279.2 440.8 272 431.1 272C423.2 272 415.1 279.2 415.1 288V368C415.1 376.8 423.2 384 431.1 384C440.8 384 447.1 376.8 447.1 368V288z"/>
          </svg>,
    };
  }

  if (iconName === 'help') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M256 0C114.6 0 0 114.6 0 256s114.6 256 256 256s256-114.6 256-256S397.4 0 256 0zM256 400c-18 0-32-14-32-32s13.1-32 32-32c17.1 0 32 14 32 32S273.1 400 256 400zM325.1 258L280 286V288c0 13-11 24-24 24S232 301 232 288V272c0-8 4-16 12-21l57-34C308 213 312 206 312 198C312 186 301.1 176 289.1 176h-51.1C225.1 176 216 186 216 198c0 13-11 24-24 24s-24-11-24-24C168 159 199 128 237.1 128h51.1C329 128 360 159 360 198C360 222 347 245 325.1 258z"/>
          </svg>,
    };
  }

  if (iconName === 'copy') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
               height={iconSize}>
            <path fill={iconColors[iconColor]}
                  d="M502.6 70.63l-61.25-61.25C435.4 3.371 427.2 0 418.7 0H255.1c-35.35 0-64 28.66-64 64l.0195 256C192 355.4 220.7 384 256 384h192c35.2 0 64-28.8 64-64V93.25C512 84.77 508.6 76.63 502.6 70.63zM464 320c0 8.836-7.164 16-16 16H255.1c-8.838 0-16-7.164-16-16L239.1 64.13c0-8.836 7.164-16 16-16h128L384 96c0 17.67 14.33 32 32 32h47.1V320zM272 448c0 8.836-7.164 16-16 16H63.1c-8.838 0-16-7.164-16-16L47.98 192.1c0-8.836 7.164-16 16-16H160V128H63.99c-35.35 0-64 28.65-64 64l.0098 256C.002 483.3 28.66 512 64 512h192c35.2 0 64-28.8 64-64v-32h-47.1L272 448z"/>
          </svg>,
    };
  }

  if (iconName === 'info') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
               height={iconSize}>
            <path d="M256 512c141.4 0 256-114.6 256-256S397.4 0 256 0S0 114.6 0 256S114.6 512 256 512zM216 336h24V272H216c-13.3 0-24-10.7-24-24s10.7-24 24-24h48c13.3 0 24 10.7 24 24v88h8c13.3 0 24 10.7 24 24s-10.7 24-24 24H216c-13.3 0-24-10.7-24-24s10.7-24 24-24zm40-144c-17.7 0-32-14.3-32-32s14.3-32 32-32s32 14.3 32 32s-14.3 32-32 32z"/>
        </svg>,


    };
  }

  if (iconName === 'list') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
               height={iconSize}>
            <path d="M184.1 38.2c9.9 8.9 10.7 24 1.8 33.9l-72 80c-4.4 4.9-10.6 7.8-17.2 7.9s-12.9-2.4-17.6-7L39 113c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l22.1 22.1 55.1-61.2c8.9-9.9 24-10.7 33.9-1.8zm0 160c9.9 8.9 10.7 24 1.8 33.9l-72 80c-4.4 4.9-10.6 7.8-17.2 7.9s-12.9-2.4-17.6-7L39 273c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l22.1 22.1 55.1-61.2c8.9-9.9 24-10.7 33.9-1.8zM256 96c0-17.7 14.3-32 32-32H512c17.7 0 32 14.3 32 32s-14.3 32-32 32H288c-17.7 0-32-14.3-32-32zm0 160c0-17.7 14.3-32 32-32H512c17.7 0 32 14.3 32 32s-14.3 32-32 32H288c-17.7 0-32-14.3-32-32zM192 416c0-17.7 14.3-32 32-32H512c17.7 0 32 14.3 32 32s-14.3 32-32 32H224c-17.7 0-32-14.3-32-32zM80 464c-26.5 0-48-21.5-48-48s21.5-48 48-48s48 21.5 48 48s-21.5 48-48 48z"/>
            </svg>,


    };
  }

  if (iconName === 'external-link') {
    renderedIcon = {
      html:
          <svg aria-hidden="true" focusable="false" role="img"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
               height={iconSize}>
            <path d="M384 32c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96C0 60.7 28.7 32 64 32H384zM160 144c-13.3 0-24 10.7-24 24s10.7 24 24 24h94.1L119 327c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l135-135V328c0 13.3 10.7 24 24 24s24-10.7 24-24V168c0-13.3-10.7-24-24-24H160z"/>
        </svg>,


    };
  }

  return (
      <div className={'rsssl-icon rsssl-icon-' + iconName}>
        {renderedIcon.html}
      </div>
  );

};

export default Icon;
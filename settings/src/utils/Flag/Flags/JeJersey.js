import * as React from "react";
const SvgJeJersey = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="JE_-_Jersey_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#JE_-_Jersey_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0h16v12H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#F7FCFF"
        stroke="#E31D1C"
        strokeWidth={1.35}
        d="M-1.35.622-2.376.003V12.397l1.024-.62 8.27-5 .954-.577-.955-.578-8.269-5Z"
      />
      <path
        fill="#F7FCFF"
        stroke="#E31D1C"
        strokeWidth={1.35}
        d="m17.46.616 1.014-.589v12.346l-1.014-.59-8.609-5L7.846 6.2l1.005-.584 8.61-5Z"
      />
      <path
        fill="#E31D1C"
        fillRule="evenodd"
        d="M6.837 2.352S6.408 4.25 7.89 4.98c0 0 1.424-.776 1.104-2.629 0 0-.611-.204-1.097-.204s-1.06.205-1.06.205Z"
        clipRule="evenodd"
      />
      <path
        fill="#FECA00"
        d="m6.71 2.548-.324-.946c.605-.208 1.128-.314 1.574-.314.458 0 .935.111 1.43.329l-.401.915c-.375-.164-.718-.244-1.029-.244-.322 0-.74.085-1.25.26ZM7.925 3.95a.625.625 0 1 0 0-1.25.625.625 0 0 0 0 1.25Z"
      />
    </g>
  </svg>
);
export default SvgJeJersey;

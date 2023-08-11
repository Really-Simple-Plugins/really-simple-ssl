import * as React from "react";
const SvgGiGibraltar = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="GI_-_Gibraltar_svg__a"
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
    <g mask="url(#GI_-_Gibraltar_svg__a)">
      <path fill="#F7FCFF" d="M0 0h16v12H0z" />
      <path
        fill="#C51918"
        fillRule="evenodd"
        d="M0 8h16v4H0V8Z"
        clipRule="evenodd"
      />
      <path
        fill="#DB000B"
        fillRule="evenodd"
        d="M8.229.633h-.947v.848h.467l-.033 2.33H6.334V2.753h.474v-.847H5.86v.424h-.474v-.424h-.948v.847h.474v1.06h-.999v.848h.474v2.542h-.474v.848h9v-.848h-.947V4.66h.947v-.848h-.928l.023-1.06h.483v-.847h-.947v.424h-.474v-.424h-.947v.847h.464l-.023 1.06H9.137L9.17 1.48h.48V.633h-.947v.424h-.474V.633Z"
        clipRule="evenodd"
      />
      <path
        fill="#272727"
        fillRule="evenodd"
        d="M5.808 6.845a.553.553 0 1 1 1.105 0v.914H5.808v-.914ZM9.86 6.845a.553.553 0 0 1 1.106 0v.914H9.86v-.914ZM7.65 6.663a.737.737 0 0 1 1.474 0v1.463H7.65V6.663Z"
        clipRule="evenodd"
      />
      <path
        fill="#E8AA00"
        fillRule="evenodd"
        d="m8.661 7.599-.09.166-.169-.252-1.379.925 1.538.732 1.356-.887-1.256-.684Zm-.63.765.444-.297.469.255-.422.276-.49-.234ZM7.579 10.267h.737V11h-.737v-.733Z"
        clipRule="evenodd"
      />
      <path
        fill="#E8AA00"
        fillRule="evenodd"
        d="M8 9h.874v2H8V9Z"
        clipRule="evenodd"
      />
    </g>
  </svg>
);
export default SvgGiGibraltar;
